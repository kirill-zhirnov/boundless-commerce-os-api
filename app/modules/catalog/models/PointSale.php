<?php

namespace app\modules\catalog\models;

use Yii;
use app\modules\system\models\Site;

/**
 * This is the model class for table "point_sale".
 *
 * @property int $point_id
 * @property int|null $site_id
 *
 * @property FinalPrice[] $finalPrices
 * @property Orders[] $orders
 * @property Site $site
 */
class PointSale extends \yii\db\ActiveRecord
{
	const DEFAULT_POINT = 1;

	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'point_sale';
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
			[['site_id'], 'default', 'value' => null],
			[['site_id'], 'integer'],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'point_id' => 'Point ID',
			'site_id' => 'Site ID',
		];
	}

	/**
	 * Gets query for [[FinalPrices]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getFinalPrices()
	{
		return $this->hasMany(FinalPrice::class, ['point_id' => 'point_id']);
	}

	/**
	 * Gets query for [[Orders]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getOrders()
	{
		return $this->hasMany(Orders::className(), ['point_id' => 'point_id']);
	}

	/**
	 * Gets query for [[Site]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getSite()
	{
		return $this->hasOne(Site::class, ['site_id' => 'site_id']);
	}
}
