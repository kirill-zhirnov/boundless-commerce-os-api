<?php

namespace app\modules\files\formModels;
use app\components\s3Buckets\InstanceBucketTools;
use app\helpers\File;
use app\modules\files\validators\InstanceSpaceValidator;
use app\modules\manager\models\Instance;
use app\modules\system\models\ConsumedSpace;
use app\validators\UuidValidator;
use yii\base\Model;
use app\modules\files\models\ApiFileUploader;
use Ramsey\Uuid\Uuid;
use Yii;
use yii\helpers\ArrayHelper;

class FileChunkUploaderForm extends Model
{
	public $file;
	public $file_name;
	public $chunks_number;
	public $chunk_position;
	public $upload_session_id;

	protected ?ApiFileUploader $initialRow;

	protected bool $isSaved = false;
	protected ?array $s3UploadedInfo;

	protected ?InstanceBucketTools $bucketTools;

	public function rules(): array
	{
		return $this->getBasicRules();
	}

	public function save(): bool
	{
		if (!$this->validate()) {
			return false;
		}

		return true;
	}

	protected function saveFileFromChunks(): void
	{
		$totalUploaded = (int)ApiFileUploader::find()->where(['file_id' => $this->upload_session_id])->count();
		if ($totalUploaded != $this->chunks_number) {
			return;
		}

		$parts = [];
		/** @var ApiFileUploader[] $rows */
		$rows = ApiFileUploader::find()->where(['file_id' => $this->upload_session_id])->orderBy(['chunk_position' => SORT_ASC])->all();
		foreach ($rows as $row) {
			$parts[] = $row->data['chunk'];
		}

		$s3Client = $this->getInstanceBucketTools()->getS3Client();
		$bucket = $this->getInstanceBucketTools()->getBucket();

		$s3Client->completeMultipartUpload([
			'Bucket' => $bucket,
			'Key' => $this->getInstanceBucketTools()->makeObjectKeyByPath($this->initialRow->data['s3Path']),
			'UploadId' => $this->initialRow->data['s3UploadId'],
			'MultipartUpload' => [
				'Parts' => $parts
			],
		]);
		$this->isSaved = true;

		$this->s3UploadedInfo = [
			'bucket' => $bucket,
			'localPath' => $this->initialRow->data['s3Path']
		];

		$this->onFileUploaded($this->initialRow->data['s3Path']);
//		$result = $s3Client->headObject([
//			'Bucket' => $bucket,
//			'Key' => $this->initialRow->data['s3Path']
//		]);
//		$fileSize = $result->get('ContentLength');

		ConsumedSpace::increaseConsumedByUploadedChunks($this->upload_session_id);

		ApiFileUploader::deleteAll(['file_id' => $this->upload_session_id]);
		ApiFileUploader::cleanUpOutdated();
	}

	protected function onFileUploaded(string $localPath)
	{}

	protected function uploadChunkToS3()
	{
		$s3PartNumber = $this->chunk_position + 1;
		$s3Client = $this->getInstanceBucketTools()->getS3Client();

		$s3UploadResult = $s3Client->uploadPart([
			'Key' => $this->getInstanceBucketTools()->makeObjectKeyByPath($this->initialRow->data['s3Path']),
			'PartNumber' => $s3PartNumber,
			'UploadId' => $this->initialRow->data['s3UploadId'],
			'Bucket' => $this->getInstanceBucketTools()->getBucket(),
			'Body' => fopen($_FILES['file']['tmp_name'], 'r'),
		]);

		ApiFileUploader::saveS3Chunk($this->upload_session_id, $this->chunk_position, [
			'ETag' => $s3UploadResult->get('ETag'),
			'PartNumber' => $s3PartNumber
		], $_FILES['file']['size']);
	}

	protected function setupInitialRow()
	{
		$this->initialRow = ApiFileUploader::initUploadSess(
			$this->upload_session_id,
			$this->file_name,
			$this->chunk_position,
			null
		);

		//this is the first chunk - find available bucket and init S3 Multipart Upload
		if (!isset($this->initialRow->data['s3UploadId'])) {
			$this->setupBucketForUpload();
		}
	}

	/**
	 * Later will be a bucket search
	 */
	protected function setupBucketForUpload()
	{
		$bucketTools = $this->getInstanceBucketTools();

		$mimeType = mime_content_type($_FILES['file']['tmp_name']);
		$info = pathinfo($this->file_name);
		$fileExt = mb_strtolower($info['extension']);

		$subFolder = File::getTypeByExt($fileExt) ?? 'others';
		$s3Path = $bucketTools->findUniqueName($fileExt, $subFolder, true);

		$s3UploadParams = [
			'Bucket' => $bucketTools->getBucket(),
			'Key' => $bucketTools->makeObjectKeyByPath($s3Path),
		];

		if ($mimeType) {
			$s3UploadParams['ContentType'] = $mimeType;
		}

		$s3MultipartUpload = $bucketTools->getS3Client()->createMultipartUpload($s3UploadParams);
		$s3UploadId = $s3MultipartUpload->get('UploadId');

		$this->initialRow->data = ArrayHelper::merge($this->initialRow->data, [
//			later, in multi-bucket architecture:
//			'bucket' => $bucket,
			's3UploadId' => $s3UploadId,
			's3Path' => $s3Path,
			'mimeType' => $mimeType
		]);
		$this->initialRow->save(false);
	}

	public function getBasicRules(): array
	{
		return [
			[['file_name'], 'required'],
			[['chunks_number'], 'integer', 'min' => 1],
			[['chunk_position'], 'integer', 'min' => 0],
			[['upload_session_id'], UuidValidator::class],
			[
				['upload_session_id'],
				'validateOnSingleUpload',
				'skipOnEmpty' => false
			],
			['file', 'validateMinChunkSize', 'skipOnEmpty' => false],
			[
				'file',
				InstanceSpaceValidator::class,
				'fileSize' => $_FILES['file']['size'] ?? 0,
				'skipOnEmpty' => false
			],
		];
	}

	public function validateOnSingleUpload()
	{
		//if there is a single upload (not by chunk - set all these attr to default values):
		if (!isset($this->chunks_number) && !isset($this->chunk_position) && !isset($this->upload_session_id)) {
			$this->chunks_number = 1;
			$this->chunk_position = 0;

			$uuid = Uuid::uuid7();
			$this->upload_session_id = $uuid->toString();
			return;
		}

		$emptyMsg = Yii::t('app', 'Required. If you are uploading a file less than 5MB - skip: chunks_number, chunk_position and upload_session_id. On other cases all three attribute should be filled.');
		if (!isset($this->chunks_number)) {
			$this->addError('chunks_number', $emptyMsg);
			return;
		} else {
			$this->chunks_number = intval($this->chunks_number);
		}


		if (!isset($this->chunk_position)) {
			$this->addError('chunk_position', $emptyMsg);
			return;
		} else {
			$this->chunk_position = intval($this->chunk_position);
		}

		if (!isset($this->upload_session_id)) {
			$this->addError('upload_session_id', $emptyMsg);
			return;
		}
	}

	public function validateMinChunkSize()
	{
		if (empty($_FILES['file'])) {
			$this->addError('file', Yii::t('app', 'File is empty. Please upload a file body with name "file".'));
			return;
		}

		$size5Mb = 1024 * 1024 * 5;
		if ($_FILES['file']['size'] > $size5Mb) {
			$this->addError('file', Yii::t('app', 'Maximum file size is 5MB.'));
			return;
		}

		if ($this->chunks_number == 1 || ($this->chunk_position + 1) == $this->chunks_number) {
			return;
		}

		if ($_FILES['file']['size'] < $size5Mb) {
			$this->addError('file', Yii::t('app', 'Minimal chunk size is 5MB, you uploaded {size}.', [
				'size' => File::formatSize($_FILES['file']['size'])
			]));
			return;
		}
	}

	public function getInstanceBucketTools(): InstanceBucketTools
	{
		if (!isset($this->bucketTools)) {
			/** @var Instance $instance */
			$instance = Yii::$app->user->getIdentity()->getInstance();
			$this->bucketTools = $instance->makeInstanceBucketTools();
		}

		return $this->bucketTools;
	}

	public function isFileSaved(): bool
	{
		return $this->isSaved;
	}
}
