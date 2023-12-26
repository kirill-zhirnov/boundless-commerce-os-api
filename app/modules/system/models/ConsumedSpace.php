<?php

namespace app\modules\system\models;

use Yii;

/**
 * This is the model class for table "consumed_space".
 *
 * @property int $space_id
 * @property string $type
 * @property int $volume
 * @property string|null $bucket
 * @property string $updated_at
 */
class ConsumedSpace extends \yii\db\ActiveRecord
{
	const SPACE_TYPE_S3 = 's3';
	const SPACE_TYPE_DB = 'db';

	const BUCKET_SYSTEM = '__system';

	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'consumed_space';
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
			[['type'], 'required'],
			[['type'], 'string'],
			[['volume'], 'default', 'value' => null],
			[['volume'], 'integer'],
			[['updated_at'], 'safe'],
			[['bucket'], 'string', 'max' => 100],
			[['bucket'], 'unique'],
			[['type'], 'unique'],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'space_id' => 'Space ID',
			'type' => 'Type',
			'volume' => 'Volume',
			'bucket' => 'Bucket',
			'updated_at' => 'Updated At',
		];
	}

	public static function calcSpaceByType(): array
	{
		$values = ['s3' => 0, 'db' => 0];
		$rows = self::getDb()->createCommand("
				select
					type, sum(volume) as volume
				from
					consumed_space
				group by type
		")
			->queryAll()
		;

		foreach ($rows as $row) {
			$values[$row['type']] = intval($row['volume']);
		}

		return $values;
	}

	public static function increaseConsumedByUploadedChunks(string $fileId, string $bucket = self::BUCKET_SYSTEM)
	{
		self::getDb()->createCommand("
			update consumed_space
			set
				volume = volume + (select sum(file_size) from api_file_uploader where file_id = :fileId)
			where
				type = :s3
				and bucket = :bucket
		")
			->bindValues([
				'fileId' => $fileId,
				's3' => self::SPACE_TYPE_S3,
				'bucket' => self::BUCKET_SYSTEM
			])
			->execute()
		;
	}
}
