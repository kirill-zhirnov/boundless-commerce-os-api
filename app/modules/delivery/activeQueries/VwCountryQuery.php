<?php

namespace app\modules\delivery\activeQueries;

use app\modules\system\models\Lang;
use yii\db\ActiveQuery;

class VwCountryQuery extends ActiveQuery
{
	public function publicOptions(): self
	{
		$this
			->where(['vw_country.lang_id' => Lang::DEFAULT_LANG])
			->orderBy(['vw_country.title' => SORT_ASC])
		;

		return $this;
	}
}
