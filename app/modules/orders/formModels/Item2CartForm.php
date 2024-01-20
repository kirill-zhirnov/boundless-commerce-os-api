<?php

namespace app\modules\orders\formModels;

use app\modules\catalog\models\FinalPrice;
use app\modules\catalog\models\PointSale;
use app\modules\catalog\models\Price;
use app\modules\catalog\models\Variant;
use app\modules\inventory\models\InventoryItem;
use app\modules\orders\components\OrderItems;
use app\modules\orders\models\ItemPrice;
use app\modules\system\models\Lang;
use app\modules\user\models\Person;
use Yii;
use app\modules\catalog\models\Product;
use app\modules\inventory\models\VwInventoryItem;
use app\modules\orders\models\Basket;
use yii\base\Model;
use yii\db\ActiveQuery;
use app\validators\UuidValidator;
use yii\web\User;

class Item2CartForm extends Model
{
	public $cart_id;
	public $item_id;
	public $qty;
	public $validate_stock;
	public $price_id;
	public $price_alias;

	protected InventoryItem|null $inventoryItem;

	protected Basket|null $basket;

	protected ?Price $price;

	public function rules(): array
	{
		return [
			[['cart_id', 'item_id', 'qty'], 'required'],
			[['item_id'], 'integer', 'min' => 0],
			[['cart_id'], UuidValidator::class],
			[['qty'], 'integer', 'min' => 1, 'max' => 32767],
			[['validate_stock'], 'boolean'],
			[['item_id'], 'validateItem'],
			[['cart_id'], 'validateBasket'],
			['price_id', 'validatePrice', 'skipOnEmpty' => false],
			['price_alias', 'safe']
		];
	}

	public function save()
	{
		if (!$this->validate()) {
			return false;
		}

		if ($this->inventoryItem->product && $this->inventoryItem->product->has_variants) {
			return $this->chooseVariant();
		}

		/** @var VwInventoryItem $vwInventoryItem */
		$vwInventoryItem = VwInventoryItem::find()
			->where(['item_id' => $this->item_id])
			->one()
		;

		if ($vwInventoryItem->isInActive()) {
			$this->addError('item_id', 'Item is archived or hidden and cannot be added to the cart.');
			return false;
		}

		if ($this->validate_stock) {
			if (!$vwInventoryItem->isInStock()) {
				$this->addError('item_id', Yii::t('app', 'Item "{title}" is out of stock.', [
					'title' => $vwInventoryItem->getTitle()
				]));
				return false;
			}

			if ($vwInventoryItem->shallTrackInventory() && $vwInventoryItem->available_qty < $this->qty) {
				$error = Yii::t('app', 'Item "{title}" is out of stock.', ['title' => $vwInventoryItem->getTitle()]);
				$error .= ' ' . Yii::t('app', 'Available {available}, Requested: {requested}', [
					'available' => $vwInventoryItem->available_qty,
					'requested' => $this->qty
				]);

				$this->addError('item_id', $error);
				return false;
			}
		}

		if (isset($this->price)) {
			$finalPrice = FinalPrice::find()
				->where([
					'point_id' => PointSale::DEFAULT_POINT,
					'item_id' => $this->item_id,
					'price_id' => $this->price->price_id
				])
				->one()
			;
			$price = $finalPrice->toArray();
		} else {
			$price = $vwInventoryItem->getSellingPrice();
		}

//		if (is_null($price)) {
//			$this->addError('item_id', 'Products without price cannot be added to the cart.');
//			return false;
//		}

		$itemPrice = ItemPrice::makeByFinalPrice($price);

		$orderItems = new OrderItems(basket: $this->basket);
		$orderItems->addItem($this->inventoryItem, intval($this->qty), $itemPrice);

		return [
			'result' => true,
			'cartTotal' => $this->basket->calcTotal(),
			'added' => [
				'item' => $vwInventoryItem,
				'qty' => intval($this->qty),
				'price' => $itemPrice
			]
		];
	}

	protected function chooseVariant(): array
	{
		/** @var Product $product */
		$product = Product::find()
			->addSelect('product.*')
			->addInventoryItemSelect()
//			->addProductPriceSelect()
			->addProductImagesSelect()
			->with(['productTexts' => function (ActiveQuery $query) {
				$query->where(['product_text.lang_id' => Lang::DEFAULT_LANG]);
			}])
			->with(['productProp'])
			->withFinalPrices()
			->andWhere(['product.product_id' => $this->inventoryItem->product_id])
			->one()
		;

		$product->setExtendedVariants(Variant::loadVariantsForTpl($product->product_id));

		return [
			'actionRequired' => 'chooseVariant',
			'product' => $product
		];
	}

	public function validateItem()
	{
		$this->inventoryItem = InventoryItem::find()
			->with('product')
			->where(['item_id' => $this->item_id])
			->one()
		;

		if (!$this->inventoryItem) {
			$this->addError('item_id', 'Item not found');
			return;
		}
	}

	public function validateBasket()
	{
		$this->basket = Basket::find()
			->where([
				'public_id' => $this->cart_id,
				'is_active' => true
			])
			->one()
		;

		if (!$this->basket) {
			$this->addError('cart_id', 'Cart not found or not active.');
			return;
		}
	}

	public function validatePrice()
	{
		if (empty($this->price_id) && empty($this->price_alias)) {
			return;
		}

		$query = Price::find();

		$attr = null;
		/** @var User $customerUser */
		$customerUser = Yii::$app->customerUser;
		if ($customerUser->isGuest) {
			$query->where('price.is_public is true');
		} else {
			/** @var Person $person */
			$person = $customerUser->getIdentity()->getPerson();

			$query->where('
				price.is_public is true
				or exists (
					select 1
					from
						person_group_rel
						inner join price_group_rel using(group_id)
					where
						person_id = :personId
						and price.price_id = price_group_rel.price_id
				)
			', ['personId' => $person->person_id]);
		}

		if ($this->price_id) {
			$attr = 'price_id';
			$query->andWhere(['price_id' => intval($this->price_id)]);
		} else {
			$attr = 'price_alias';
			$query->andWhere(['alias' => $this->price_alias]);
		}

		$this->price = $query->one();
		if (!$this->price) {
			$this->addError($attr, Yii::t('app', 'Price not found. Make sure your user has access to the selected price'));
			return;
		}
	}
}
