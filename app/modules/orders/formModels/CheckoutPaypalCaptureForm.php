<?php

namespace app\modules\orders\formModels;

use app\modules\payment\components\PayPal;
use yii\base\Model;

class CheckoutPaypalCaptureForm extends Model
{
	public $id;

	public function rules(): array
	{
		return [
			[['id'], 'required'],
		];
	}

	public function save(): array|false
	{
		if (!$this->validate()) {
			return false;
		}

		try {
			$paypal = new PayPal();
			$result = $paypal->approveAuthorizedOrder($this->id);
			$order = $paypal->getOrder();

			$out = ['result' => $result];

			if ($order) {
				$out['order'] = $order;
			}

			return $out;
		} catch (\Exception $e) {
			$this->addError('id', $e->getMessage());
			return false;
		}
	}
}
