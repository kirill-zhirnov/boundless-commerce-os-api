<?php

namespace app\modules\system\controllers;

use app\components\RestController;
use app\modules\system\formModels\WixSiteSettingsForm;
use app\modules\system\models\Setting;
use yii\base\DynamicModel;
use yii\helpers\ArrayHelper;
use yii\web\ServerErrorHttpException;
use Yii;

class SettingsController extends RestController
{
	protected function verbs(): array
	{
		return [
			'wix-site' => ['GET'],
			'save-wix-site' => ['POST'],
		];
	}

	public function actionWixSite($mode = null)
	{
		$key = ($mode === 'draft') ? Setting::KEY_WIX_SITE_DRAFT_SETTINGS : Setting::KEY_WIX_SITE_LIVE_SETTINGS;

		$value = Setting::getCachedSetting(Setting::GROUP_CMS, $key);

		if (empty($value)) {
			return new \stdClass();
		} else {
			return $value;
		}
	}

	public function actionSaveWixSite()
	{
		$model = new WixSiteSettingsForm();
		$model->load(Yii::$app->getRequest()->getBodyParams(), '');

		if ($model->save() === false && !$model->hasErrors()) {
			throw new ServerErrorHttpException('Failed to update the object for unknown reason.');
		}

		return $model;
	}

	public function actionFetch()
	{
		$model = DynamicModel::validateData(ArrayHelper::merge(['keys' => ''], Yii::$app->getRequest()->getQueryParams()), [
			[['keys'], 'required'],
			[['keys'], 'each', 'rule' => ['string', 'message' => 'Key must be an array of strings.']],
			[['keys'], 'each', 'rule' => ['match', 'pattern' => '/^[a-z]+\.[a-z]+$/i', 'message' => 'Invalid key format - a dot required.']],
		]);

		if ($model->hasErrors()) {
			return $model;
		}

		$out = [];
		foreach ($model->keys as $key) {
			list($group, $settingKey) = explode('.', $key);
			if (!in_array($group, [Setting::GROUP_INVENTORY, Setting::GROUP_ORDERS, Setting::GROUP_SYSTEM, Setting::GROUP_CMS])) {
				$model->addError('keys', 'Invalid group: ' . $group);
				return $model;
			}

			if ($key === 'system.currency') {
				$out[$key] = Setting::getCurrency();
			} else {
				$out[$key] = Setting::getCachedSetting($group, $settingKey);
			}
		}

		return $out;
	}
}
