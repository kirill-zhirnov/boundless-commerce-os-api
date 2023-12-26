<?php

namespace app\modules\catalog\searchModels;

use app\modules\catalog\models\Category;
use app\modules\catalog\models\Product;
use app\modules\catalog\models\VwCategoryOption;
use app\modules\system\models\Lang;
use yii\base\Model;
use yii\web\BadRequestHttpException;

class CategoryTreeSearch extends Model
{
	public $menu;

	public $calc_products;

	public function rules(): array
	{
		return [
			['menu', 'in', 'range' => ['category']],
			['calc_products', 'in', 'range' => ['1']],
		];
	}

	public function search(array $params = []): array
	{
		$this->load($params, '');
		if (!$this->validate()) {
			throw new BadRequestHttpException(implode(' ', $this->getErrorSummary(false)));
		}

		$query = VwCategoryOption::find();
		$query
			->alias('vw')
			->select('vw.*')
			->with(['categoryProp', 'image'])
			->where([
				'vw.status' => Category::STATUS_PUBLISHED,
				'vw.deleted_at' => null,
				'vw.lang_id' => Lang::DEFAULT_LANG,
			])
			->orderBy(['vw.tree_sort' => SORT_ASC])
		;

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
			$query->andWhere('vw.category_id is not null');
		}

		$out = [];
		/** @var VwCategoryOption $row */
		foreach ($query->all() as $row) {
			$outRow = [
				'category_id' => $row->category_id,
				'parent_id' => $row->parent_id,
				'title' => $row->title,
				'url_key' => $row->url_key,
				'tree_sort' => $row->tree_sort,
				'level' => $row->level,
				'image' => $row->image
			];

			if ($row->categoryProp) {
				$outRow['custom_link'] = $row->categoryProp->custom_link;
			}

			if ($this->calc_products) {
				$outRow['products_qty'] = $row->products_qty ? intval($row->products_qty) : 0;
			}

			if (is_null($row->parent_id)) {
				$parent = &$out;
			} else {
				$parentRow = &$this->findParent($out, $row->parent_id);

				if (!$parentRow) {
					continue;
				}

				if (!isset($parentRow['children'])) {
					$parentRow['children'] = [];
				}

				$parent = &$parentRow['children'];
			}

			$parent[] = $outRow;
		}

		return $out;
	}

	protected function &findParent(array &$out, int $parentId): array|null
	{
		foreach ($out as &$outRow) {
			if ($outRow['category_id'] == $parentId) {
				return $outRow;
			} elseif (isset($outRow['children'])) {
				$childResult = &$this->findParent($outRow['children'], $parentId);
				if ($childResult) {
					return $childResult;
				}
			}
		}

		$falseResult = null;
		return $falseResult;
	}
}
