<?php

namespace app\helpers;

use yii\base\Model;

class Models
{
	public static function emptyStr2Null(Model $model, array $fields)
	{
		foreach ($fields as $field) {
			if ($model->{$field} === '') {
				$model->{$field} = null;
			}
		}
	}
}
