<?php

namespace app\modules\system\formModels;

use app\modules\system\models\Setting;
use yii\base\Model;

class WixSiteSettingsForm extends Model
{
	public $mode;

	public $settings;

	public function rules(): array
	{
		return [
			[['mode', 'settings'], 'required'],
			[['mode'], 'in', 'range' => ['draft', 'live']],
			[['settings'], 'validateSettings'],
		];
	}

	public function save(): bool
	{
		if (!$this->validate()) {
			return false;
		}

		$key = ($this->mode === 'draft') ? Setting::KEY_WIX_SITE_DRAFT_SETTINGS : Setting::KEY_WIX_SITE_LIVE_SETTINGS;
		Setting::setSetting(Setting::GROUP_CMS, $key, $this->settings);

		return true;
	}

	public function validateSettings()
	{
		if (!is_array($this->settings)) {
			$this->addError('settings', 'Settings must be an object');
			return;
		}

		if (empty($this->settings)) {
			$this->addError('settings', 'Settings are empty.');
			return;
		}
	}
}
