<?php

namespace app\components\actions;

use yii\web\ErrorAction;

class JsonErrorAction extends ErrorAction
{
	public function run()
	{
		return $this->getViewRenderParams();
	}
}
