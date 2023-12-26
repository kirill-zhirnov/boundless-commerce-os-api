<?php

namespace app\modules\user\searchModels;

use app\modules\user\models\Person;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\web\BadRequestHttpException;

class CustomerSearch extends Model
{
	public $name;
	public $email;
//	public $address;
//	public $country_title;

	public function rules(): array
	{
		return [
			[['name', 'email'], 'string'],
			[['name', 'email'], 'trim'],
			[['name', 'email'], 'filter', 'filter' => 'mb_strtolower'],
		];
	}

	public function search(array $params = []): ActiveDataProvider
	{
		$this->load($params, '');
		if (!$this->validate()) {
			throw new BadRequestHttpException(implode(' ', $this->getErrorSummary(false)));
		}

		$query = Person::find()
			->innerJoinWith('personProfile')
			->with(['personAddresses'])
			->where(['person.status' => Person::STATUS_PUBLISHED])
			->andWhere('person.deleted_at is null')
		;

		$query
			->andFilterWhere(['like', "lower(concat(person_profile.first_name, ' ', person_profile.last_name))", $this->name])
			->andFilterWhere(['like', "person.email", $this->email])
		;

		$dataProvider = new ActiveDataProvider([
			'query' => $query,
			'sort' => [
				'attributes' => [
					'created_at',
					'total_price'
				],
				'defaultOrder' => [
					'created_at' => SORT_DESC
				],
			],
		]);

		return $dataProvider;
	}
}
