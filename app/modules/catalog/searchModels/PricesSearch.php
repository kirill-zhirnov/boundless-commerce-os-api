<?php

namespace app\modules\catalog\searchModels;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\web\BadRequestHttpException;
use app\modules\catalog\models\Price;

class PricesSearch extends Model
{
	public function rules(): array
	{
		return [
		];
	}

	public function search(array $params = []): ActiveDataProvider
	{
		$this->load($params, '');
		if (!$this->validate()) {
			throw new BadRequestHttpException(implode(' ', $this->getErrorSummary(false)));
		}

		$query = Price::find()
			->with(['customerGroups', 'priceTextDefault'])
			->where(['deleted_at' => null])
		;

		return new ActiveDataProvider([
			'query' => $query,
			'sort' => [
				'defaultOrder' => [
					'sort' => SORT_ASC
				]
			]
		]);
	}
}
