<?php

namespace app\modules\system\models;

use Yii;

/**
 * This is the model class for table "essence".
 *
 * @property int $essence_id
 * @property string $type
 * @property int $essence_local_id
 *
 * @property AdminComment[] $adminComments
 */
class Essence extends \yii\db\ActiveRecord
{
	const TYPE_ORDERS = 'orders';

	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'essence';
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
			[['type', 'essence_local_id'], 'required'],
			[['type'], 'string'],
			[['essence_local_id'], 'default', 'value' => null],
			[['essence_local_id'], 'integer'],
			[['type', 'essence_local_id'], 'unique', 'targetAttribute' => ['type', 'essence_local_id']],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'essence_id' => Yii::t('app', 'Essence ID'),
			'type' => Yii::t('app', 'Type'),
			'essence_local_id' => Yii::t('app', 'Essence Local ID'),
		];
	}

	/**
	 * Gets query for [[AdminComments]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getAdminComments()
	{
		return $this->hasMany(AdminComment::class, ['essence_id' => 'essence_id']);
	}

	public static function findOrCreateEssence(int $localId, string $type): self
	{
		static::getDb()->createCommand("
			insert into essence
				(type, essence_local_id)
			values
				(:type, :localId)
			on conflict do nothing
		")
			->bindValues([
				'type' => $type,
				'localId' => $localId
			])
			->execute()
		;

		return self::find()
			->where(['type' => $type, 'essence_local_id' => $localId])
			->one()
		;
	}
}
