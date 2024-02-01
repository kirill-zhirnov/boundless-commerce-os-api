<?php

namespace app\modules\orders\models;

use Yii;
use app\modules\user\models\Person;

/**
 * This is the model class for table "basket".
 *
 * @property int $basket_id
 * @property int|null $person_id
 * @property bool $is_active
 * @property string $public_id
 * @property string $created_at
 *
 * @property BasketItem[] $basketItems
 * @property InventoryItem[] $items
 * @property Orders $order
 * @property Person $person
 */
class Basket extends \yii\db\ActiveRecord
{
	public $total;

	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'basket';
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
			[['person_id'], 'default', 'value' => null],
			[['person_id'], 'integer'],
			[['is_active'], 'boolean'],
			[['public_id'], 'string'],
			[['public_id'], 'unique'],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'basket_id' => 'Basket ID',
			'person_id' => 'Person ID',
			'is_active' => 'Is Active',
			'public_id' => 'Public ID',
		];
	}

	/**
	 * Gets query for [[BasketItems]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getBasketItems()
	{
		return $this->hasMany(BasketItem::class, ['basket_id' => 'basket_id']);
	}

	/**
	 * Gets query for [[Items]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getItems()
	{
		return $this->hasMany(InventoryItem::className(), ['item_id' => 'item_id'])->viaTable('basket_item', ['basket_id' => 'basket_id']);
	}

	/**
	 * Gets query for [[Orders]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getOrder()
	{
		return $this->hasOne(Orders::class, ['basket_id' => 'basket_id']);
	}

	/**
	 * Gets query for [[Person]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getPerson()
	{
		return $this->hasOne(Person::class, ['person_id' => 'person_id']);
	}

	public function setTotal(array $total): self
	{
		$this->total = $total;
		return $this;
	}

	public function calcTotal(): array
	{
		$this->total = $this->getDb()->createCommand("
			select
				coalesce(sum(qty), 0) as qty,
				coalesce(sum(final_price * qty), 0) as total
			from
				basket_item
				inner join basket using(basket_id)
				inner join item_price using(item_price_id)
			where
				basket.basket_id = :id
				and basket_item.deleted_at is null
		")
			->bindValues(['id' => $this->basket_id])
			->queryOne()
		;

		return $this->total;
	}

	public function bindDraftOrder()
	{
		$this->getDb()->createCommand("
			insert into orders
				(basket_id, publishing_status)
			values
				(:basket, :status)
			on conflict do nothing
		")
			->bindValues([
				'basket' => $this->basket_id,
				'status' => Orders::STATUS_DRAFT
			])
			->execute()
		;
	}

	public function makeInactive()
	{
		$this->is_active = false;
		if (!$this->save(false)) {
			throw new \RuntimeException('Cannot make basket inactive:' . print_r($this->getErrors(), 1));
		}
	}

	public function isActive(): bool
	{
		return $this->is_active;
	}

	public function copyItemsTo(Basket $toBasket)
	{
		self::getDb()
			->createCommand("
				insert into basket_item (basket_id, item_id, qty, item_price_id)
				select
					:toBasketId, item_id, qty, item_price_id
				from
					basket_item
				where
					basket_id = :currentBasketId
					and deleted_at is null
					and not exists(
						select 1 from basket_item to_basket_item
					 	where
							to_basket_item.item_id = basket_item.item_id
					    and to_basket_item.basket_id = :toBasketId
					)
			")
			->bindValues([
				'toBasketId' => $toBasket->basket_id,
				'currentBasketId' => $this->basket_id
			])
			->execute();
		;
	}

	public static function findOrCreatePersonBasket(Person $person): Basket
	{
		$row = self::getDb()
			->createCommand("select * from basket_get(:personId)")
			->bindValues(['personId' => $person->person_id])
			->queryOne()
		;

		return Basket::findOne($row['basket_id']);
	}

	public function fields(): array
	{
//		$fields = parent::fields();
		$out = [
			'id' => function (self $model) {
				return $model->public_id;
			},
			'created_at' => function (self $model) {
				return $model->created_at;
			},
		];

		if (isset($this->total)) {
			$out['total'] = function (self $model) {
				return $model->total;
			};
		}

		return $out;
	}
}
