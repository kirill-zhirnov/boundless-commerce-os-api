<?php

namespace app\modules\catalog\models;

use Yii;

/**
 * This is the model class for table "product_label_rel".
 *
 * @property int $label_id
 * @property int $product_id
 * @property string $created_at
 *
 * @property Label $label
 * @property Product $product
 */
class ProductLabelRel extends \yii\db\ActiveRecord
{
	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'product_label_rel';
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
			[['label_id', 'product_id'], 'required'],
			[['label_id', 'product_id'], 'default', 'value' => null],
			[['label_id', 'product_id'], 'integer'],
			[['created_at'], 'safe'],
			[['label_id', 'product_id'], 'unique', 'targetAttribute' => ['label_id', 'product_id']],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'label_id' => 'Label ID',
			'product_id' => 'Product ID',
			'created_at' => 'Created At',
		];
	}

	/**
	 * Gets query for [[Label]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getLabel()
	{
		return $this->hasOne(Label::class, ['label_id' => 'label_id']);
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

	public static function setLabelRels(int $productId, array $labelIds)
	{
		if (!empty($labelIds)) {
			$db = self::getDb();
			$labelIds = array_map(function ($val) use($db) {
				return $db->quoteValue($val);
			}, $labelIds);
			$db->createCommand("
				insert into product_label_rel
					(label_id, product_id)
				select
					label_id, :productId
				from
					label
				where
					label_id in (" . implode(',', $labelIds) . ")
				on conflict (product_id, label_id)
				do nothing
			")
				->bindValues(['productId' => $productId])
				->execute()
			;
		}

		$deleteCondition = ['product_id' => $productId];
		if (!empty($labelIds)) {
			$deleteCondition = [
				'and',
				['product_id' => $productId],
				['not in', 'label_id', $labelIds]
			];
		}
		ProductLabelRel::deleteAll($deleteCondition);
	}
}
