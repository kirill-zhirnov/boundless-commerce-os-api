<?php

namespace app\modules\catalog\activeQueries;

use app\modules\catalog\models\Product;
use app\modules\system\models\Lang;
use yii\db\ActiveQuery;

class CategoryQuery extends ActiveQuery
{
	public function withProductsQty(): self
	{
		$this->leftJoin(
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
			'calc_products.category_id = category.category_id',
			['productPublished' => Product::STATUS_PUBLISHED]
		);
		$this->addSelect(['coalesce(calc_products.products_qty, 0) as products_qty']);

		return $this;
	}

	public function withPublicScope(): self
	{
		$this
			->with(['categoryTexts' => function (ActiveQuery $query) {
				$query->where(['category_text.lang_id' => Lang::DEFAULT_LANG]);
			}])
			->with(['categoryProp', 'image'])
		;

		return $this;
	}
}
