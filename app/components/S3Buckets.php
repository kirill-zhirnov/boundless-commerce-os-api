<?php

namespace app\components;

use yii\base\Component;
use Aws\S3\S3Client;
use Aws\Credentials\Credentials;
use app\modules\manager\models\Instance;
use app\components\s3Buckets\InstanceBucketTools;

class S3Buckets extends Component
{
	public ?array $locations;

	public ?string $defaultBucket;

	public function init()
	{
		parent::init();

		if (!isset($this->locations)) {
			$conf = self::getConfByEnv();

			$this->locations = $conf['locations'];
			$this->defaultBucket = $conf['defaultBucket'];
		}
	}

	public function makeInstanceBucketTools(Instance $instance): InstanceBucketTools
	{
		$s3Client = $this->makeDefaultS3Client();
		$config = $this->getDefaultLocationConfig();

		return new InstanceBucketTools($s3Client, $this->defaultBucket, $instance, $config['folderPrefix']);
	}

	public function makeDefaultS3Client(): S3Client
	{
		if (!isset($this->locations[$this->defaultBucket])) {
			throw new \RuntimeException('defaultBucket doesnt exists in locations. S3_LOCATIONS_DEFAULT specified?');
		}

		return $this->makeS3Client($this->defaultBucket);
	}

	public function getDefaultLocationConfig(): array
	{
		return $this->getLocationConfig($this->defaultBucket);
	}

	public function getLocationConfig(string $key): array
	{
		if (!isset($this->locations[$key])) {
			throw new \RuntimeException('key "' . $key . '" doesnt exist in locations');
		}

		return $this->locations[$key];
	}

	public function makeS3Client(string $alias): S3Client
	{
		if (!isset($this->locations[$alias])) {
			throw new RuntimeException('Key "' . $alias . '" is not exist in locations');
		}

		$credentials = new Credentials($this->locations[$alias]['key'], $this->locations[$alias]['secret']);
		return new S3Client([
			'bucket_endpoint' => true,
			'region' => $this->locations[$alias]['region'],
			'version' => 'latest',
			'endpoint' => 'https://' . $this->locations[$alias]['host'],
			'credentials' => $credentials,
		]);
	}

	public static function getConfByEnv(): array
	{
		$locations = [];
		$qty = $_SERVER['S3_LOCATIONS_QTY'] ?? 0;
		$defaultBucket = $_SERVER['S3_LOCATIONS_DEFAULT'] ?? '';

		for ($i = 1; $i <= $qty; $i++) {
			$key = 'S3_LOCATION_' . $i;
			if (!isset($_SERVER[$key])) {
				throw new RuntimeException('ENV variable "' . $key . '" doesnt exist');
			}

			list($bucket, $host, $region, $volume, $key, $secret, $folderPrefix) = explode(':', $_SERVER[$key]);
			if (trim($folderPrefix) == '') {
				$folderPrefix = null;
			}

			if (isset($locations[$bucket])) {
				throw new RuntimeException('Bucket name "' . $bucket . '" should be unique across all locations.');
			}

			$locations[$bucket] = [
				'host' => $host,
				'region' => $region,
				//size in bits, convert GB to bits
				'volume' => $volume * 1024 * 1024 * 1024,
				'key' => $key,
				'secret' => $secret,
				'folderPrefix' => $folderPrefix
			];
		}

		return [
			'locations' => $locations,
			'defaultBucket' => $defaultBucket
		];
	}
}
