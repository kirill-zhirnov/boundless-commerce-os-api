<?php

namespace app\modules\files\controllers\admin;

use app\components\filters\StrongToken;
use app\components\RestController;
use app\components\S3Buckets;
use app\modules\cms\models\Image;
use app\modules\files\formModels\ImageChunkUploaderForm;
use app\modules\manager\models\Instance;
use yii\helpers\ArrayHelper;
use Yii;
use yii\web\ServerErrorHttpException;
use yii\web\NotFoundHttpException;

class ManageImagesController extends RestController
{
	public function behaviors(): array
	{
		return ArrayHelper::merge(parent::behaviors(), [
			'strongToken' => [
				'class' => StrongToken::class
			]
		]);
	}

	public function actionUpload()
	{
		$model = new ImageChunkUploaderForm();
		$model->load(Yii::$app->getRequest()->getBodyParams(), '');
		if ($model->save() === false && !$model->hasErrors()) {
			throw new ServerErrorHttpException('Failed to update the object for unknown reason.');
		}

		if ($model->hasErrors())
			return $model;

		if ($model->isFileSaved()) {
			return [
				'saved' => 'file',
				'image' => $model->getImage()
			];
		} else {
			return ['saved' => 'part'];
		}
	}

	public function actionOriginalUrl($imageId)
	{
		$image = Image::findOne($imageId);

		if (!$image) {
			throw new NotFoundHttpException('Image not found');
		}

		/** @var Instance $instance */
		$instance = Yii::$app->user->getIdentity()->getInstance();
		$bucketTools = $instance->makeInstanceBucketTools();

		return [
			'url' => $bucketTools->createSignedLink($image->path)
		];
	}
}
