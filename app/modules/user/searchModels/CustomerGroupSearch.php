<?php

namespace app\modules\user\searchModels;

use app\modules\user\models\CustomerGroup;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\web\BadRequestHttpException;

class CustomerGroupSearch extends Model
{
	public $title;
	public $alias;

	public function rules(): array
	{
		return [
			[['title', 'alias'], 'string'],
		];
	}

	public function search(array $params = []): ActiveDataProvider
	{
		$this->load($params, '');
		if (!$this->validate()) {
			throw new BadRequestHttpException(implode(' ', $this->getErrorSummary(false)));
		}

		$query = CustomerGroup::find()
			->where(['deleted_at' => null])
		;

		$query
			->andFilterWhere(['like', 'customer_group.title', $this->title])
			->andFilterWhere(['like', 'customer_group.alias', $this->alias])
		;

		$dataProvider = new ActiveDataProvider([
			'query' => $query,
			'sort' => [
				'defaultOrder' => [
					'title' => SORT_ASC
				],
			],
		]);

		return $dataProvider;
	}
}
