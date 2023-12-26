<?php

namespace app\modules\catalog\searchModels;

use Yii;
use yii\base\Model;
use yii\db\Expression;
use yii\web\BadRequestHttpException;
use yii\db\Query;

class CategoryParentsSearch extends Model
{
	public $category;

	public function rules(): array
	{
		return [
			['category', 'required'],
			['category', 'integer'],
		];
	}

	public function search(array $params = []): array
	{
		$this->load($params, '');
		if (!$this->validate()) {
			throw new BadRequestHttpException(implode(' ', $this->getErrorSummary(false)));
		}

		$query = new Query();
		$query
			->from(new Expression('category_get_parents(:category) as parents'))
			->addParams(['category' => $this->category])
			->orderBy(['parents.tree_sort' => SORT_ASC])
		;

		return $query->all(Yii::$app->get('instanceDb'));
	}
}
