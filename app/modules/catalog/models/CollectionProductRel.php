<?php

namespace app\modules\catalog\models;

use Yii;

/**
 * This is the model class for table "collection_product_rel".
 *
 * @property int $rel_id
 * @property int $collection_id
 * @property int $product_id
 * @property int|null $sort
 *
 * @property Collection $collection
 * @property Product $product
 */
class CollectionProductRel extends \yii\db\ActiveRecord
{
	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'collection_product_rel';
	}

	/**
	 * @return \yii\db\Connection the database connection used by this AR class.
	 */
	public static function getDb()
	{
		return Yii::$app->get('instanceDb');
	}

	/**
	 * {@inheritdoc}
	 */
	public function rules()
	{
		return [
			[['collection_id', 'product_id'], 'required'],
			[['collection_id', 'product_id', 'sort'], 'default', 'value' => null],
			[['collection_id', 'product_id', 'sort'], 'integer'],
			[['collection_id', 'product_id'], 'unique', 'targetAttribute' => ['collection_id', 'product_id']],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'rel_id' => 'Rel ID',
			'collection_id' => 'Collection ID',
			'product_id' => 'Product ID',
			'sort' => 'Sort',
		];
	}

	/**
	 * Gets query for [[Collection]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getCollection()
	{
		return $this->hasOne(Collection::class, ['collection_id' => 'collection_id']);
	}

	/**
	 * Gets query for [[Product]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getProduct()
	{
		return $this->hasOne(Product::class, ['product_id' => 'product_id']);
	}

	public static function setCollectionRels(int $productId, array $collectionIds)
	{
		if (!empty($collectionIds)) {
			$db = self::getDb();
			$collectionIds = array_map(fn ($val) => $db->quoteValue($val), $collectionIds);
			$db->createCommand("
				insert into collection_product_rel
					(collection_id, product_id)
				select
					collection_id, :productId
				from
					collection
				where
					collection_id in (" . implode(',', $collectionIds) . ")
				on conflict (collection_id, product_id)
				do nothing
			")
				->bindValues(['productId' => $productId])
				->execute()
			;
		}

		$deleteCondition = ['product_id' => $productId];
		if (!empty($collectionIds)) {
			$deleteCondition = [
				'and',
				['product_id' => $productId],
				['not in', 'collection_id', $collectionIds]
			];
		}
		CollectionProductRel::deleteAll($deleteCondition);
	}
}
