<?php

namespace app\modules\user;

use yii\base\BootstrapInterface;

class Module extends \yii\base\Module implements BootstrapInterface
{
	public $controllerNamespace = 'app\modules\user\controllers';

	public function init()
	{
		parent::init();
	}

	public function bootstrap($app)
	{
		if ($app instanceof \yii\console\Application) {
			$this->controllerNamespace = 'app\modules\user\commands';
		}
	}
}
