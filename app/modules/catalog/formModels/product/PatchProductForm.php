<?php

namespace app\modules\catalog\formModels\product;
use app\components\InstancesQueue;
use app\modules\catalog\models\Product;
use app\modules\catalog\validators\ProductPricesValidator;
use app\modules\catalog\validators\StockPerWarehouseValidator;
use Yii;

class PatchProductForm extends ProductForm
{
	public function rules(): array
	{
		return array_merge(
			$this->getBasicRules(),
			[
				[
					'stock_per_warehouse',
					StockPerWarehouseValidator::class,
					'product' => $this->product,
					'groupIdAttribute' => 'group_id',
					'isInStockAttribute' => 'is_in_stock',
					'skipOnEmpty' => false,
				],
				[
					'prices',
					ProductPricesValidator::class,
					'product' => $this->product,
				],
			]
		);
	}

	public function save(): bool
	{
		if (!isset($this->product)) {
			throw new \RuntimeException('For Patch Model a product should be set prior calling this func');
		}

		if (!$this->validate()) {
			return false;
		}

		$this->saveProductFields();
		$this->saveProductTextFields();
		$this->saveProductPropsFields();

		$this->saveCategories();
		$this->saveLabels();
		$this->saveCollections();
		$this->savePrices();
		$this->saveStock();

		/** @var InstancesQueue $queue */
		$queue = Yii::$app->queue;
		$queue->modelUpdated(Product::class, [$this->product->product_id]);

		return true;
	}

	protected function saveProductPropsFields()
	{
		$shallSave = false;
		$productProp = $this->product->productProp;

		foreach (['dimensions', 'tax_status', 'tax_class_id', 'arbitrary_data'] as $field) {
			if (!is_null($this->{$field})) {
				$shallSave = true;

				switch ($field) {
					case 'dimensions':
						$productProp->size = $this->dimensions;
						break;
					case 'tax_status':
						$productProp->tax_status = $this->tax_status;
						break;
					case 'tax_class_id':
						$productProp->tax_class_id = $this->tax_class_id;
						break;
					case 'arbitrary_data':
						$productProp->arbitrary_data = $this->arbitrary_data;
						break;
				}
			}
		}

		if ($shallSave) {
			$productProp->save(false);
		}
	}

	protected function saveProductTextFields()
	{
		$shallSave = false;
		$productText = $this->product->productTextDefault;

		foreach (['title', 'url_key', 'description'] as $field) {
			if (!is_null($this->{$field})) {
				$shallSave = true;

				switch ($field) {
					case 'title':
						$productText->title = trim($this->title);
						break;
					case 'url_key':
						$productText->url_key = $this->url_key;
						break;
					case 'description':
						$productText->description = $this->description;
						break;
				}
			}
		}

		if ($shallSave) {
			$productText->save(false);
		}
	}

	protected function saveProductFields()
	{
		$prevGroupId = $this->product->group_id;
		$shallSave = false;
		foreach (['sku', 'manufacturer_id', 'group_id', 'external_id', 'publishing_status'] as $field) {
			if (!is_null($this->{$field})) {
				$shallSave = true;

				switch ($field) {
					case 'sku':
						$this->product->sku = $this->sku;
						break;
					case 'manufacturer_id':
						$this->product->manufacturer_id = $this->manufacturer_id;
						break;
					case 'group_id':
						$this->product->group_id = $this->group_id;
						break;
					case 'external_id':
						$this->product->external_id = $this->external_id;
						break;
					case 'publishing_status':
						$this->product->status = $this->publishing_status;
						break;
				}
			}
		}

		if (!empty($shallSave)) {
			$this->product->save(false);

			if ($this->group_id && $this->group_id != $prevGroupId) {
				$this->product->onGroupChanged($prevGroupId, $this->group_id);
			}
		}
	}
}
