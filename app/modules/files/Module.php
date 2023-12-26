<?php

namespace app\modules\files;

use yii\base\BootstrapInterface;

class Module extends \yii\base\Module implements BootstrapInterface
{
	public $controllerNamespace = 'app\modules\files\controllers';

	public function bootstrap($app)
	{
		if ($app instanceof \yii\console\Application) {
			$this->controllerNamespace = 'app\modules\files\commands';
		}
	}
}
