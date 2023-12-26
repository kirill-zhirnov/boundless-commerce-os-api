<?php

namespace app\modules\catalog\searchModels;

use app\modules\catalog\models\Filter;
use app\modules\system\models\Lang;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\db\ActiveQuery;
use yii\web\BadRequestHttpException;

class FilterSearch extends Model
{
	public $is_default;

	public function rules(): array
	{
		return [
			[['is_default'], 'in', 'range' => ['1']]
		];
	}

	public function search(array $params = []): ActiveDataProvider
	{
		$this->load($params, '');
		if (!$this->validate()) {
			throw new BadRequestHttpException(implode(' ', $this->getErrorSummary(false)));
		}

		$query = Filter::find()
			->withFields()
		;

		if ($this->is_default) {
			$query->where('filter.is_default is true');
		}

		return new ActiveDataProvider([
			'query' => $query,
			'sort' => [
				'defaultOrder' => [
					'title' => SORT_ASC
				]
			]
		]);
	}
}
