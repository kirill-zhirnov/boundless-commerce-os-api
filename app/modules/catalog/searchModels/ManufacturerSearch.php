<?php

namespace app\modules\catalog\searchModels;

use app\modules\catalog\models\Manufacturer;
use app\modules\catalog\models\Product;
use app\modules\system\models\Lang;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\db\ActiveQuery;
use yii\web\BadRequestHttpException;
use Yii;

class ManufacturerSearch extends Model
{
	public $title;

	public $calc_products;

	public $category;

	public function rules(): array
	{
		return [
			['title', 'safe'],
			['calc_products', 'in', 'range' => ['1']],
			['category', 'each', 'rule' => ['integer']],
		];
	}

	public function search(array $params = []): ActiveDataProvider
	{
		$this->load($params, '');
		if (!$this->validate()) {
			throw new BadRequestHttpException(implode(' ', $this->getErrorSummary(false)));
		}

		$query = Manufacturer::find()
			->select('manufacturer.*')
			->innerJoinWith(['manufacturerTexts' => function (ActiveQuery $query) {
				$query->where(['manufacturer_text.lang_id' => Lang::DEFAULT_LANG]);
			}])
			->with(['image'])
		;

		$query
			->andFilterWhere(['like', 'title', $this->title])
		;

		if ($this->calc_products) {
			$query->leftJoin(
				'(
					select
						manufacturer_id, count(product_id) as products_qty
					from
						product
					where
						product.status = :productPublished
						and product.deleted_at is null
					group by manufacturer_id
				) as calc_products',
				'calc_products.manufacturer_id = manufacturer.manufacturer_id',
				[
					'productPublished' => Product::STATUS_PUBLISHED
				]
			);
			$query->addSelect(['calc_products.products_qty as products_qty']);
		}

		if ($this->category) {
			$db = Yii::$app->get('instanceDb');
			$categories = array_map(function ($categoryId) use ($db) {
				return $db->quoteValue($categoryId);
			}, $this->category);

			$query->andWhere("
				exists (
					select
						1
					from
						product
						inner join product_category_rel using(product_id)
					where
						product.manufacturer_id = manufacturer.manufacturer_id
						and product.status = 'published'
						and product.deleted_at is null
						and product_category_rel.category_id in (" . implode(', ', $categories) . ")
				)
			");
		}

		$dataProvider = new ActiveDataProvider([
			'query' => $query,
			'sort' => [
				'attributes' => [
					'title' => [
						'asc' => ['manufacturer_text.title' => SORT_ASC],
						'desc' => ['manufacturer_text.title' => SORT_DESC],
						'default' => SORT_ASC,
					],
				],
				'defaultOrder' => [
					'title' => SORT_ASC
				],
			]
		]);

		return $dataProvider;
	}
}
