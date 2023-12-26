<?php

namespace app\modules\orders\formModels;

use app\modules\catalog\models\Variant;
use app\modules\inventory\models\InventoryItem;
use app\modules\orders\components\OrderItems;
use app\modules\orders\models\ItemPrice;
use app\modules\system\models\Lang;
use Yii;
use app\modules\catalog\models\Product;
use app\modules\inventory\models\VwInventoryItem;
use app\modules\orders\models\Basket;
use yii\base\Model;
use yii\db\ActiveQuery;
use app\validators\UuidValidator;

class Item2CartForm extends Model
{
	public $cart_id;
	public $item_id;
	public $qty;
	public $validate_stock;

	protected InventoryItem|null $inventoryItem;

	protected Basket|null $basket;

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

		$price = $vwInventoryItem->getSellingPrice();
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
				'qty' => intval($this->qty)
			]
		];
	}

	protected function chooseVariant(): array
	{
		/** @var Product $product */
		$product = Product::find()
			->addSelect('product.*')
			->addInventoryItemSelect()
			->addProductPriceSelect()
			->addProductImagesSelect()
			->with(['productTexts' => function (ActiveQuery $query) {
				$query->where(['product_text.lang_id' => Lang::DEFAULT_LANG]);
			}])
			->with(['productProp'])
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
}
