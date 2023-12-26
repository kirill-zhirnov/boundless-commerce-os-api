<?php

namespace app\modules\catalog\searchModels;

use app\modules\catalog\models\CommodityGroup;
use app\modules\catalog\models\VwCharacteristicGrid;
use app\modules\system\models\Lang;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\db\ActiveQuery;
use yii\web\BadRequestHttpException;

class CharacteristicSearch extends Model
{
	public $id;
	public $group_id;
	public $alias;

	protected ?CommodityGroup $commodityGroup;

	public function rules(): array
	{
		return [
			['group_id', 'integer'],
			['id', 'each', 'rule' => ['integer']],
			['alias', 'each', 'rule' => ['string']],
		];
	}

	public function search(array $params = []): ActiveDataProvider
	{
		$this->load($params, '');
		if (!$this->validate()) {
			throw new BadRequestHttpException(implode(' ', $this->getErrorSummary(false)));
		}

		$query = VwCharacteristicGrid::find()
			->alias('vw')
			->with(['characteristicTypeCases' => function (ActiveQuery $query) {
				$query->orderBy(['sort' => SORT_ASC]);
			}])
			->with(['characteristicTypeCases.characteristicTypeCaseTexts' => function (ActiveQuery $query) {
				$query->where(['characteristic_type_case_text.lang_id' => Lang::DEFAULT_LANG]);
			}])
			->where(['vw.lang_id' => Lang::DEFAULT_LANG])
		;

		$query->andFilterWhere(['vw.group_id' => $this->group_id]);
		$query->andFilterWhere(['vw.characteristic_id' => $this->id]);
		$query->andFilterWhere(['vw.alias' => $this->alias]);

		$dataProvider = new ActiveDataProvider([
			'query' => $query,
			'sort' => [
				'attributes' => [
					'tree_sort' => [
						'asc' => ['vw.tree_sort' => SORT_ASC],
						'desc' => ['vw.tree_sort' => SORT_DESC],
						'default' => SORT_ASC,
					],
				],
				'defaultOrder' => [
					'tree_sort' => SORT_ASC
				]
			],
			'pagination' => [
				'pageSizeLimit' => [1, 200],
				'defaultPageSize' => 100
			]
		]);

		return $dataProvider;
	}

	public function setCommodityGroup(CommodityGroup $commodityGroup): self
	{
		$this->commodityGroup = $commodityGroup;
		return $this;
	}
}
