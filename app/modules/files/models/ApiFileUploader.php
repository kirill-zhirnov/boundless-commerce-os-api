<?php

namespace app\modules\files\models;

use app\helpers\RandomFilePath;
use Yii;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "api_file_uploader".
 *
 * @property int $id
 * @property string $file_id
 * @property string|null $path
 * @property int $chunk_position
 * @property bool $is_initial
 * @property string $data
 * @property string $created_at
 * @property int $file_size
 */
class ApiFileUploader extends \yii\db\ActiveRecord
{
	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'api_file_uploader';
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
			[['file_id', 'chunk_position'], 'required'],
			[['file_id'], 'string'],
			[['chunk_position'], 'default', 'value' => null],
			[['chunk_position', 'file_size'], 'integer'],
			[['is_initial'], 'boolean'],
			[['data', 'created_at'], 'safe'],
			[['path'], 'string', 'max' => 500],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'id' => 'ID',
			'file_id' => 'File ID',
			'path' => 'Path',
			'chunk_position' => 'Chunk Position',
			'is_initial' => 'Is Initial',
			'data' => 'Data',
			'created_at' => 'Created At',
		];
	}

	public static function saveS3Chunk(string $fileId, int $position, array $chunk, int $fileSize = 0)
	{
		self::getDb()
			->createCommand("
				insert into api_file_uploader
					(file_id, chunk_position, data, file_size)
				values
					(:fileId, :position, :data, :fileSize)
				on conflict (file_id, chunk_position)
				do update set
					data = jsonb_set(api_file_uploader.data::jsonb, '{chunk}', :chunk, true)::json,
					file_size = :fileSize
			")
			->bindValues([
				'fileId' => $fileId,
				'position' => $position,
				'data' => json_encode(['chunk' => $chunk]),
				'chunk' => json_encode($chunk),
				'fileSize' => $fileSize
			])
			->execute()
		;
	}

	public static function cleanUpOutdated()
	{
		self::getDb()
			->createCommand("delete from api_file_uploader where created_at < now() - interval '24 hours'")
			->execute();
		;
	}

	public static function initUploadSess(string $fileId, string $fileName, int $chunkPosition, string|null $saveInDir, array $data = []): self
	{
		/** @var ApiFileUploader $initialRow */
		$initialRow = self::find()
			->where(['file_id' => $fileId, 'is_initial' => true])
			->one()
		;

		$data = ArrayHelper::merge(['fileName' => $fileName], $data);

		if (!$initialRow) {
			//if a file needs to be saved locally
			if ($saveInDir !== null) {
				$info = pathinfo($fileName);
				$fileExt = mb_strtolower($info['extension']);
				$randomPath = new RandomFilePath($saveInDir, $fileExt, 1);

				$saveAtPath = $randomPath->obtainPath();
				$pathInfo = pathinfo($saveAtPath);

				$chunksDir = $pathInfo['dirname'] . '/' . $pathInfo['filename'];
				$data = ArrayHelper::merge([
					'chunksDir' => $chunksDir,
					'saveAtPath' => $saveAtPath,
				], $data);
			}

			$initialRow = new self();
			$initialRow->attributes = [
				'file_id' => $fileId,
				'chunk_position' => $chunkPosition,
				'is_initial' => true,
				'data' => $data
			];
			$initialRow->save(false);
		}

		if (isset($initialRow->data['chunksDir'])) {
			$chunksDir = $initialRow->data['chunksDir'];
			if (!is_dir($chunksDir)) {
				mkdir($chunksDir, 0777, true);
				@chmod($chunksDir, 0777);
			}
		}

		return $initialRow;
	}

}
