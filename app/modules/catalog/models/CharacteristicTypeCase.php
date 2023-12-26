<?php

namespace app\modules\catalog\models;

use app\modules\system\models\Lang;
use Yii;

/**
 * This is the model class for table "characteristic_type_case".
 *
 * @property int $case_id
 * @property int $characteristic_id
 * @property int|null $sort
 *
 * @property Characteristic $characteristic
 * @property CharacteristicProductVal[] $characteristicProductVals
 * @property CharacteristicTypeCaseText[] $characteristicTypeCaseTexts
 * @property CharacteristicVariantVal[] $characteristicVariantVals
 * @property CharacteristicTypeCaseText $textDefault
 */
class CharacteristicTypeCase extends \yii\db\ActiveRecord
{
	public $products_qty;

	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'characteristic_type_case';
	}

	/**
	 * @return \yii\db\Connection the database connection used by this AR class.
	 */
	public static function getDb()
	{
		return Yii::$app->get('instanceDb');
	}

	/**
	 * {@inheritdoc}
	 */
	public function rules()
	{
		return [
			[['characteristic_id'], 'required'],
			[['characteristic_id', 'sort'], 'default', 'value' => null],
			[['characteristic_id', 'sort'], 'integer'],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'case_id' => 'Case ID',
			'characteristic_id' => 'Characteristic ID',
			'sort' => 'Sort',
		];
	}

	/**
	 * Gets query for [[Characteristic]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getCharacteristic()
	{
		return $this->hasOne(Characteristic::class, ['characteristic_id' => 'characteristic_id']);
	}

	/**
	 * Gets query for [[CharacteristicProductVals]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getCharacteristicProductVals()
	{
		return $this->hasMany(CharacteristicProductVal::className(), ['case_id' => 'case_id']);
	}

	/**
	 * Gets query for [[CharacteristicTypeCaseTexts]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getCharacteristicTypeCaseTexts()
	{
		return $this->hasMany(CharacteristicTypeCaseText::class, ['case_id' => 'case_id']);
	}

	public function getTextDefault()
	{
		return $this
			->hasOne(CharacteristicTypeCaseText::class, ['case_id' => 'case_id'])
			->andWhere(['characteristic_type_case_text.lang_id' => Lang::DEFAULT_LANG])
		;
	}

	/**
	 * Gets query for [[CharacteristicVariantVals]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getCharacteristicVariantVals()
	{
		return $this->hasMany(CharacteristicVariantVal::className(), ['case_id' => 'case_id']);
	}

	public function fields(): array
	{
		$out = ['case_id'];
		$out['title'] = function (self $model) {
			return $model->characteristicTypeCaseTexts[0]?->title;
		};


		if (isset($this->products_qty)) {
			$out['products_qty'] = function () {
				return $this->products_qty;
			};
		}
		$out[] = 'sort';

		return $out;
	}
}
