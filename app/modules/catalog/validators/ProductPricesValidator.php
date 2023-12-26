<?php

namespace app\modules\catalog\validators;

use app\modules\catalog\models\Price;
use app\modules\catalog\models\Product;
use yii\validators\Validator;
use Yii;

class ProductPricesValidator extends Validator
{
	public ?Product $product;

	public function validateAttribute($model, $attribute)
	{
		$result = $this->validateValue($model->$attribute);
		if ($result !== null) {
			$this->addError($model, $attribute, $result[0]);
			return;
		}
	}

	protected function validateValue($value): null|array
	{
		if (is_null($value)) {
			return null;
		}

		if (!is_array($value)) {
			return [Yii::t('app', 'Prices should be an object, where keys are "selling_price" or "purchase_price" or any other price_alias.'), []];
		}

		if (isset($this->product) && $this->product->has_variants) {
			return [Yii::t('app', 'Product has Variants. Prices should be set on Variants level.'), []];
		}

		foreach ($value as $key => $row) {
			if (!in_array($key, [Price::ALIAS_SELLING_PRICE, Price::ALIAS_PURCHASE_PRICE])) {
				$priceRow = Price::findOne(['alias' => $key]);
				if (!$priceRow) {
					return [Yii::t('app', 'Price with alias "{key}" doesnt exist.', ['key' => $key]), []];
				}
			}

			if (!is_array($row) || !array_key_exists('price',  $row)) {
				return [Yii::t('app', 'Key "{key}" has incorrect value. The value should be an object with key "price" and optional "compareAtPrice"', [
					'key' => $key
				]), []];
			}

			if (!is_numeric($row['price']) && $row['price'] !== null) {
				return [Yii::t('app', 'Key "{key}" has incorrect "price" value. Price should be either null or numeric.', [
					'key' => $key
				]), []];
			}

			if (array_key_exists('compareAtPrice',  $row) && !is_numeric($row['compareAtPrice']) && $row['compareAtPrice'] !== null) {
				return [Yii::t('app', 'Key "{key}" has incorrect "compareAtPrice" value. Price should be either null or numeric.', [
					'key' => $key
				]), []];
			}
		}

		return null;
	}
}
