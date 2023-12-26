<?php

namespace app\modules\files\validators;

use app\helpers\File;
use app\modules\manager\models\Feature;
use app\modules\manager\models\Instance;
use app\modules\system\models\ConsumedSpace;
use yii\validators\Validator;
use Yii;

class InstanceSpaceValidator extends Validator
{
	public int $fileSize = 0;

	public function init()
	{
		parent::init();

		if ($this->message === null) {
			$this->message = Yii::t('app', 'Tariff\'s storage limit is reached. Storage limit by tariff: {limitSize}, consumed: {consumedSize}.');
		}
	}

	public function validateAttribute($model, $attribute)
	{
		$result = $this->validateValue($model->$attribute);
		if ($result !== null) {
			$this->addError($model, $attribute, $result[0], $result[1]);
			return;
		}
	}

	protected function validateValue($value): null|array
	{
		$instance = $this->findInstance();
		if (!isset($instance->tariff)) {
			return null;
		}

		$limits = $instance->tariff->getFeaturesLimits();
		if (empty($limits[Feature::ALIAS_STORAGE_LIMIT])) {
			return null;
		}

		//size is in MB -> B
		$storageLimit = $limits[Feature::ALIAS_STORAGE_LIMIT] * 1024 * 1024;
		$consumed = ConsumedSpace::calcSpaceByType();
		$consumedWithFile = ($consumed['s3'] ?? 0) + $this->fileSize;

		if ($storageLimit < $consumedWithFile) {
			return [$this->message, [
				'limitSize' => File::formatSize($storageLimit),
				'consumedSize' => File::formatSize($consumedWithFile),
			]];
		}

		return null;
	}

	protected function findInstance(): Instance
	{
//		$dbName = Setting::extractDbName();
//		if (!preg_match('/^i(\d+)$/', $dbName, $matches)) {
//			throw new \RuntimeException('Cant extract instance ID');
//		}
//		$instanceId = intval($matches[1]);
//
//		return Instance::findOne($instanceId);

		/** @var Instance $instance */
		$instance = Yii::$app->user->getIdentity()->getInstance();

		return $instance;
	}
}
