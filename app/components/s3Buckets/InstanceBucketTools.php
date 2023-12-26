<?php

namespace app\components\s3Buckets;
use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use app\modules\manager\models\Instance;
use Ramsey\Uuid\Uuid;

class InstanceBucketTools
{
	public function __construct(
		protected S3Client $s3Client,
		protected string $bucket,
		protected Instance $instance,
		protected string|null $folderPrefix = null
	)
	{}

	public function getS3Client(): S3Client
	{
		return $this->s3Client;
	}

	public function getFolderPrefix(): ?string
	{
		return $this->folderPrefix;
	}

	public function getBucket(): string
	{
		return $this->bucket;
	}

	public function makeObjectKeyByPath(string $localPath): string
	{
		$key = "i{$this->instance->instance_id}/{$localPath}";

		if ($this->folderPrefix) {
			$prefix = $this->folderPrefix;
			if (!str_ends_with($prefix, '/')) {
				$prefix .= '/';
			}

			$key = "{$prefix}{$key}";
		}

		return $key;
	}

	public function isExists(string $localPath): bool
	{
		try {
			$result = $this->s3Client->headObject([
				'Bucket' => $this->bucket,
				'Key' => $this->makeObjectKeyByPath($localPath)
			]);

			return true;
		} catch (S3Exception $e) {
			return false;
		}
	}

	public function findUniqueName(string $ext, string $folderPrefix = '', bool $addFolderSubPrefix = false): string
	{
		$counter = 0;
		do {
			$fileName = sha1(Uuid::uuid7()) . '-' . $counter . '.' . $ext;

			if ($addFolderSubPrefix) {
				$subPrefix = substr(sha1(Uuid::uuid7()), 0, 2);
				$fileName = $subPrefix . '/' . $fileName;
			}

			if ($folderPrefix) {
				$fileName = rtrim($folderPrefix, '/') . '/' . $fileName;
			}

			if (!$this->isExists($fileName)) {
				return $fileName;
			}

			$counter++;
		} while (true);
	}

	public function downloadFile(string $localPath, string $saveAs): void
	{
		$this->s3Client->getObject([
			'Bucket' => $this->bucket,
			'Key' => $this->makeObjectKeyByPath($localPath),
			'SaveAs' => $saveAs
		]);
	}

	public function createSignedLink(string $localPath, ?int $expiresIn = 3600): string
	{
		$command = $this->s3Client->getCommand('GetObject', [
			'Bucket' => $this->bucket,
			'Key' => $this->makeObjectKeyByPath($localPath)
		]);

		$request = $this->s3Client->createPresignedRequest($command, '+ ' . $expiresIn . ' seconds');

		return (string) $request->getUri();
	}
}
