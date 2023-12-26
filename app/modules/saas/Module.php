<?php

namespace app\modules\saas;

use yii\base\BootstrapInterface;

class Module extends \yii\base\Module implements BootstrapInterface
{
	public $controllerNamespace = 'app\modules\saas\controllers';

	public function bootstrap($app)
	{
		if ($app instanceof \yii\console\Application) {
			$this->controllerNamespace = 'app\modules\saas\commands';
		}
	}
}
