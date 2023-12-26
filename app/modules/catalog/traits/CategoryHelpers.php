<?php

namespace app\modules\catalog\traits;

use app\modules\catalog\models\Category;
use yii\web\NotFoundHttpException;

trait CategoryHelpers
{
	public function findCategoryById(int|string $id): Category
	{
		$id = intval($id);

		/** @var Category $category */
		$category = Category::find()
			->where(['category_id' => $id])
			->one();
		;

		if (!$category) {
			throw new NotFoundHttpException('Category isnt found');
		}

		return $category;
	}
}
