<?php

namespace app\modules\delivery\activeQueries;

use app\modules\system\models\Lang;
use yii\db\ActiveQuery;

class DeliveryQuery extends ActiveQuery
{
	public function publicOptions(): self
	{
		$this
			->innerJoinWith('deliverySite', false)
			->with(['deliveryText', 'vwShipping'])
			->where([
				'deleted_at' => null,
				'status' => 'published'
			])
			->orderBy(['delivery_site.sort' => SORT_ASC])
		;

		return $this;
	}
}
