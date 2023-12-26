<?php

namespace app\modules\inventory\activeQueries;

use app\modules\catalog\models\Product;
use app\modules\inventory\models\VwInventoryItem;
use yii\db\ActiveQuery;

class VwInventoryItemQuery extends ActiveQuery
{
	public function onlyActive(): self
	{
		$this->andWhere('(status = :published and deleted_at is null) or "type" = :customItem', [
			'published' => Product::STATUS_PUBLISHED,
			'customItem' => VwInventoryItem::TYPE_CUSTOM_ITEM
		]);

		return $this;
	}
}
