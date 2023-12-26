<?php

namespace app\modules\catalog\searchModels;

use app\modules\catalog\models\CommodityGroup;
use app\modules\system\models\Lang;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\db\ActiveQuery;
use yii\web\BadRequestHttpException;

class CommodityGroupSearch extends Model
{
	public function rules(): array
	{
		return [];
	}

	public function search(array $params = []): ActiveDataProvider
	{
		$this->load($params, '');
		if (!$this->validate()) {
			throw new BadRequestHttpException(implode(' ', $this->getErrorSummary(false)));
		}

		$query = CommodityGroup::find()
			->joinWith(['commodityGroupTexts' => function (ActiveQuery $query) {
				$query->where(['commodity_group_text.lang_id' => Lang::DEFAULT_LANG]);
			}])
			->with(['characteristics' => function (ActiveQuery $query) {
				$query->orderBy(['sort' => SORT_ASC]);
			}])
			->with(['characteristics.characteristicTexts' => function (ActiveQuery $query) {
				$query->where(['characteristic_text.lang_id' => Lang::DEFAULT_LANG]);
			}])
			->with(['characteristics.characteristicProp'])
			->with(['characteristics.characteristicTypeCases' => function (ActiveQuery $query) {
				$query->orderBy(['sort' => SORT_ASC]);
			}])
			->with(['characteristics.characteristicTypeCases.characteristicTypeCaseTexts' => function (ActiveQuery $query) {
				$query->where(['characteristic_type_case_text.lang_id' => Lang::DEFAULT_LANG]);
			}])
			->where(['commodity_group.deleted_at' => null])
		;

		$dataProvider = new ActiveDataProvider([
			'query' => $query,
			'sort' => [
				'attributes' => [
					'title' => [
						'asc' => ['commodity_group_text.title' => SORT_ASC],
						'desc' => ['commodity_group_text.title' => SORT_DESC],
						'default' => SORT_ASC,
					],
				],
				'defaultOrder' => [
					'title' => SORT_ASC
				]
			]
		]);

		return $dataProvider;
	}
}
