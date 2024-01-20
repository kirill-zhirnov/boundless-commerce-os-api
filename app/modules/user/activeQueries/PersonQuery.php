<?php

namespace app\modules\user\activeQueries;

use yii\db\ActiveQuery;

class PersonQuery extends ActiveQuery
{
	public function publicPersonScope(): self
	{
		$this->with(['personProfile', 'personAddresses', 'customerGroups']);

		return $this;
	}
}
