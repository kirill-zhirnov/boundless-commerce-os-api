<?php

namespace app\modules\orders\validators;

use app\modules\orders\components\OrderItems;
use app\modules\orders\models\Basket;
use app\modules\system\models\Setting;
use yii\validators\Validator;
use Yii;

class MinOrderAmountValidator extends Validator
{
	public ?Basket $basket;

	public function validateAttribute($model, $attribute)
	{
		if (!isset($this->basket)) {
			throw new RuntimeException('Basket should be set prior calling this func');
		}

		$minOrderAmount = Setting::getMinOrderAmount();
		if (!empty($minOrderAmount) && is_numeric($minOrderAmount) && $minOrderAmount > 0) {
			$orderItems = new OrderItems(basket: $this->basket);
			$total = $orderItems->calcTotal();

			if (bccomp($minOrderAmount, $total['price'], 2) == 1) {
				$currency = Setting::getCurrencyAlias();
				/** @var \yii\i18n\Formatter $formatter */
				$formatter = Yii::$app->formatter;

				$this->addError(
					$model,
					$attribute,
					Yii::t('app', 'The minimal order amount is {minPrice}. Please add some products to the cart.', [
						'minPrice' => $formatter->asCurrency($minOrderAmount, $currency)
					])
				);
				return;
			}
		}
	}
}
