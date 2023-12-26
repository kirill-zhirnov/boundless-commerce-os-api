<?php

namespace app\modules\files\commands;

use app\components\S3Buckets;
use yii\console\Controller;
use yii\helpers\Console;
use Yii;

class S3Controller extends Controller
{
	public function actionTestConnection($bucket)
	{
		$this->stdout("Testing connection for: '". $bucket . "'\n", Console::FG_GREEN);

		/** @var S3Buckets $s3Buckets */
		$s3Buckets = Yii::$app->s3Buckets;
		$s3Client = $s3Buckets->makeS3Client($bucket);

		$s3Client->listObjectsV2(['Bucket' => $bucket]);

		$this->stdout("Success\n");

		$this->stdout("Testing default bucket\n", Console::FG_GREEN);
		$s3Buckets->makeDefaultS3Client()->listObjectsV2(['Bucket' => $s3Buckets->defaultBucket]);
		$this->stdout("Success\n");
	}
}
