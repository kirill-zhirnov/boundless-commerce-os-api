<?php

namespace app\modules\catalog\searchModels;

use app\modules\catalog\models\Category;
use app\modules\catalog\models\Product;
use app\modules\catalog\models\VwCategoryFlatList;
use app\modules\system\models\Lang;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\db\ActiveQuery;
use yii\web\BadRequestHttpException;
use Yii;

class CategoryFlatSearch extends Model
{
//	public $title;

//	public $deleted;

//	public $status;

	public $menu;

	public $calc_products;

	public $calc_children;

	public $parent;

	public $brand;

	public function rules(): array
	{
		return [
			[['calc_products', 'calc_children'], 'in', 'range' => ['1']],
//			[['status'], 'in', 'range' => ['draft']],
			[['menu'], 'in', 'range' => ['category']],
			[['parent'], 'integer', 'min' => -1],
			[['brand'], 'each', 'rule' => ['integer']]
		];
	}

	public function search(array $params = []): ActiveDataProvider
	{
		$this->load($params, '');
		if (!$this->validate()) {
			throw new BadRequestHttpException(implode(' ', $this->getErrorSummary(false)));
		}

		$query = VwCategoryFlatList::find()
			->alias('vw')
			->select('vw.*')
			->innerJoinWith('category', true)
			->with(['categoryTexts' => function (ActiveQuery $query) {
				$query->where(['category_text.lang_id' => Lang::DEFAULT_LANG]);
			}])
			->with(['categoryProp', 'category.image'])
		;

		$query->where([
//			$this->status ?:
			'category.status' => Category::STATUS_PUBLISHED,
//			'category.deleted_at' => null,
			'vw.lang_id' => Lang::DEFAULT_LANG
		]);
//		$query->andWhere($this->deleted ? 'category.deleted_at is not null' : 'category.deleted_at is null');

		if ($this->calc_children) {
			$query->leftJoin(
				'(
				select
					parent_id, count(*) as children_qty
				from
					category
				where
					category.status = :categoryPublished
					and category.deleted_at is null
				group by category.parent_id
				) as calc_children',
				'calc_children.parent_id = vw.category_id',
				['categoryPublished' => Category::STATUS_PUBLISHED]
			);
			$query->addSelect(['coalesce(calc_children.children_qty, 0) as children_qty']);
		}

		if ($this->calc_products) {
			$query->leftJoin(
				'(
					select
						category_id, count(product_id) as products_qty
					from
						product_category_rel
						inner join product using(product_id)
					where
						product.status = :productPublished
						and product.deleted_at is null
					group by category_id
					) as calc_products',
				'calc_products.category_id = vw.category_id',
				['productPublished' => Product::STATUS_PUBLISHED]
			);
			$query->addSelect(['calc_products.products_qty as products_qty']);
		}

		if ($this->menu) {
			$query->andWhere('
				exists (
					select
						1
					from
						category_menu_rel
						inner join menu_block using(block_id)
					where
						category_menu_rel.category_id = vw.category_id
						and menu_block.key = :menuKey
				)
			', ['menuKey' => $this->menu]);
		}

		if (isset($this->parent)) {
			if ($this->parent == 0) {
				$query->andWhere('category.parent_id is null');
			} else {
				$query->andWhere(['category.parent_id' => $this->parent]);
			}
		}

		if ($this->brand) {
			$db = Yii::$app->get('instanceDb');
			$brands = array_map(function ($brandId) use ($db) {
				return $db->quoteValue($brandId);
			}, $this->brand);

			$query->andWhere("
				exists (
					select
						1
					from
						product_category_rel
						inner join product using(product_id)
					where
						product_category_rel.category_id = vw.category_id
						and product.status = 'published'
						and product.deleted_at is null
						and product.manufacturer_id in (" . implode(', ', $brands) . ")
				)
			");
		}

		$dataProvider = new ActiveDataProvider([
			'query' => $query,
			'sort' => [
				'attributes' => [
					'tree_sort' => [
						'asc' => ['vw.tree_sort' => SORT_ASC],
						'desc' => ['vw.tree_sort' => SORT_DESC],
						'default' => SORT_ASC,
					],
					'title' => [
						'asc' => ['vw.joined_title' => SORT_ASC],
						'desc' => ['vw.joined_title' => SORT_DESC],
						'default' => SORT_ASC,
					],
				],
				'defaultOrder' => [
					'tree_sort' => SORT_ASC
				],
			],
//			'pagination' => [
//				'pageSize' => 2
//			]
		]);

		return $dataProvider;
	}
}
