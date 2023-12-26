<?php

namespace app\modules\system\models;

use Yii;

/**
 * This is the model class for table "lang".
 *
 * @property int $lang_id
 * @property string|null $code
 * @property bool $is_backend
 *
 * @property Article[] $articles
 * @property BoxText[] $boxTexts
 * @property Box[] $boxes
 * @property CharacteristicTypeCase[] $cases
 * @property Category[] $categories
 * @property CategoryText[] $categoryTexts
 * @property CharacteristicProductValText[] $characteristicProductValTexts
 * @property CharacteristicText[] $characteristicTexts
 * @property CharacteristicTypeCaseText[] $characteristicTypeCaseTexts
 * @property CharacteristicVariantValText[] $characteristicVariantValTexts
 * @property Characteristic[] $characteristics
 * @property Collection[] $collections
 * @property CommodityGroupText[] $commodityGroupTexts
 * @property CustomerGroupText[] $customerGroupTexts
 * @property DeliveryCityText[] $deliveryCityTexts
 * @property DeliveryCountryText[] $deliveryCountryTexts
 * @property DeliveryText[] $deliveryTexts
 * @property CommodityGroup[] $groups
 * @property Image[] $images
 * @property Import[] $imports
 * @property Lang[] $inLangs
 * @property InstagramConfig[] $instagramConfigs
 * @property ThemeInstalled[] $installeds
 * @property InventoryOptionText[] $inventoryOptionTexts
 * @property LabelText[] $labelTexts
 * @property Label[] $labels
 * @property LangTitle[] $langTitles
 * @property LangTitle[] $langTitles0
 * @property Lang[] $langs
 * @property ManufacturerText[] $manufacturerTexts
 * @property Manufacturer[] $manufacturers
 * @property MenuItem[] $menuItems
 * @property InventoryOption[] $options
 * @property OrderSourceText[] $orderSourceTexts
 * @property OrderStatusText[] $orderStatusTexts
 * @property Page[] $pages
 * @property PaymentMethodText[] $paymentMethodTexts
 * @property PaymentMethod[] $paymentMethods
 * @property PriceText[] $priceTexts
 * @property ProductImageText[] $productImageTexts
 * @property ProductImage[] $productImages
 * @property ProductImport[] $productImports
 * @property ProductText[] $productTexts
 * @property Product[] $products
 * @property ServiceText[] $serviceTexts
 * @property Service[] $services
 * @property SiteCountryLang[] $siteCountryLangs
 * @property Site[] $sites
 * @property SmsTemplate[] $smsTemplates
 * @property OrderStatus[] $statuses
 * @property ThemeInstalledText[] $themeInstalledTexts
 * @property CharacteristicProductVal[] $values
 * @property CharacteristicVariantVal[] $values0
 * @property VariantText[] $variantTexts
 * @property Variant[] $variants
 * @property WarehouseText[] $warehouseTexts
 * @property Warehouse[] $warehouses
 */
class Lang extends \yii\db\ActiveRecord
{
	const DEFAULT_LANG = 1;

	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'lang';
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
			[['is_backend'], 'boolean'],
			[['code'], 'string', 'max' => 2],
			[['code'], 'unique'],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'lang_id' => 'Lang ID',
			'code' => 'Code',
			'is_backend' => 'Is Backend',
		];
	}

	/**
	 * Gets query for [[Articles]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getArticles()
	{
		return $this->hasMany(Article::className(), ['lang_id' => 'lang_id']);
	}

	/**
	 * Gets query for [[BoxTexts]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getBoxTexts()
	{
		return $this->hasMany(BoxText::className(), ['lang_id' => 'lang_id']);
	}

	/**
	 * Gets query for [[Boxes]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getBoxes()
	{
		return $this->hasMany(Box::className(), ['box_id' => 'box_id'])->viaTable('box_text', ['lang_id' => 'lang_id']);
	}

	/**
	 * Gets query for [[Cases]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getCases()
	{
		return $this->hasMany(CharacteristicTypeCase::className(), ['case_id' => 'case_id'])
			->viaTable('characteristic_type_case_text', ['lang_id' => 'lang_id']);
	}

	/**
	 * Gets query for [[Categories]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getCategories()
	{
		return $this->hasMany(Category::className(), ['category_id' => 'category_id'])
			->viaTable('category_text', ['lang_id' => 'lang_id']);
	}

	/**
	 * Gets query for [[CategoryTexts]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getCategoryTexts()
	{
		return $this->hasMany(CategoryText::className(), ['lang_id' => 'lang_id']);
	}

	/**
	 * Gets query for [[CharacteristicProductValTexts]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getCharacteristicProductValTexts()
	{
		return $this->hasMany(CharacteristicProductValText::className(), ['lang_id' => 'lang_id']);
	}

	/**
	 * Gets query for [[CharacteristicTexts]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getCharacteristicTexts()
	{
		return $this->hasMany(CharacteristicText::className(), ['lang_id' => 'lang_id']);
	}

	/**
	 * Gets query for [[CharacteristicTypeCaseTexts]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getCharacteristicTypeCaseTexts()
	{
		return $this->hasMany(CharacteristicTypeCaseText::className(), ['lang_id' => 'lang_id']);
	}

	/**
	 * Gets query for [[CharacteristicVariantValTexts]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getCharacteristicVariantValTexts()
	{
		return $this->hasMany(CharacteristicVariantValText::className(), ['lang_id' => 'lang_id']);
	}

	/**
	 * Gets query for [[Characteristics]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getCharacteristics()
	{
		return $this->hasMany(Characteristic::className(), ['characteristic_id' => 'characteristic_id'])
			->viaTable('characteristic_text', ['lang_id' => 'lang_id']);
	}

	/**
	 * Gets query for [[Collections]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getCollections()
	{
		return $this->hasMany(Collection::className(), ['lang_id' => 'lang_id']);
	}

	/**
	 * Gets query for [[CommodityGroupTexts]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getCommodityGroupTexts()
	{
		return $this->hasMany(CommodityGroupText::className(), ['lang_id' => 'lang_id']);
	}

	/**
	 * Gets query for [[CustomerGroupTexts]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getCustomerGroupTexts()
	{
		return $this->hasMany(CustomerGroupText::className(), ['lang_id' => 'lang_id']);
	}

	/**
	 * Gets query for [[DeliveryCityTexts]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getDeliveryCityTexts()
	{
		return $this->hasMany(DeliveryCityText::className(), ['lang_id' => 'lang_id']);
	}

	/**
	 * Gets query for [[DeliveryCountryTexts]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getDeliveryCountryTexts()
	{
		return $this->hasMany(DeliveryCountryText::className(), ['lang_id' => 'lang_id']);
	}

	/**
	 * Gets query for [[DeliveryTexts]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getDeliveryTexts()
	{
		return $this->hasMany(DeliveryText::className(), ['lang_id' => 'lang_id']);
	}

	/**
	 * Gets query for [[Groups]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getGroups()
	{
		return $this->hasMany(CommodityGroup::className(), ['group_id' => 'group_id'])
			->viaTable('commodity_group_text', ['lang_id' => 'lang_id']);
	}

	/**
	 * Gets query for [[Images]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getImages()
	{
		return $this->hasMany(Image::className(), ['lang_id' => 'lang_id']);
	}

	/**
	 * Gets query for [[Imports]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getImports()
	{
		return $this->hasMany(Import::className(), ['lang_id' => 'lang_id']);
	}

	/**
	 * Gets query for [[InLangs]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getInLangs()
	{
		return $this->hasMany(Lang::className(), ['lang_id' => 'in_lang_id'])
			->viaTable('lang_title', ['lang_id' => 'lang_id']);
	}

	/**
	 * Gets query for [[InstagramConfigs]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getInstagramConfigs()
	{
		return $this->hasMany(InstagramConfig::className(), ['lang_id' => 'lang_id']);
	}

	/**
	 * Gets query for [[Installeds]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getInstalleds()
	{
		return $this->hasMany(ThemeInstalled::className(), ['installed_id' => 'installed_id'])
			->viaTable('theme_installed_text', ['lang_id' => 'lang_id']);
	}

	/**
	 * Gets query for [[InventoryOptionTexts]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getInventoryOptionTexts()
	{
		return $this->hasMany(InventoryOptionText::className(), ['lang_id' => 'lang_id']);
	}

	/**
	 * Gets query for [[LabelTexts]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getLabelTexts()
	{
		return $this->hasMany(LabelText::className(), ['lang_id' => 'lang_id']);
	}

	/**
	 * Gets query for [[Labels]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getLabels()
	{
		return $this->hasMany(Label::className(), ['label_id' => 'label_id'])
			->viaTable('label_text', ['lang_id' => 'lang_id']);
	}

	/**
	 * Gets query for [[LangTitles]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getLangTitles()
	{
		return $this->hasMany(LangTitle::className(), ['lang_id' => 'lang_id']);
	}

	/**
	 * Gets query for [[LangTitles0]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getLangTitles0()
	{
		return $this->hasMany(LangTitle::className(), ['in_lang_id' => 'lang_id']);
	}

	/**
	 * Gets query for [[Langs]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getLangs()
	{
		return $this->hasMany(Lang::className(), ['lang_id' => 'lang_id'])
			->viaTable('lang_title', ['in_lang_id' => 'lang_id']);
	}

	/**
	 * Gets query for [[ManufacturerTexts]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getManufacturerTexts()
	{
		return $this->hasMany(ManufacturerText::className(), ['lang_id' => 'lang_id']);
	}

	/**
	 * Gets query for [[Manufacturers]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getManufacturers()
	{
		return $this->hasMany(Manufacturer::className(), ['manufacturer_id' => 'manufacturer_id'])
			->viaTable('manufacturer_text', ['lang_id' => 'lang_id']);
	}

	/**
	 * Gets query for [[MenuItems]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getMenuItems()
	{
		return $this->hasMany(MenuItem::className(), ['lang_id' => 'lang_id']);
	}

	/**
	 * Gets query for [[Options]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getOptions()
	{
		return $this->hasMany(InventoryOption::className(), ['option_id' => 'option_id'])
			->viaTable('inventory_option_text', ['lang_id' => 'lang_id']);
	}

	/**
	 * Gets query for [[OrderSourceTexts]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getOrderSourceTexts()
	{
		return $this->hasMany(OrderSourceText::className(), ['lang_id' => 'lang_id']);
	}

	/**
	 * Gets query for [[OrderStatusTexts]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getOrderStatusTexts()
	{
		return $this->hasMany(OrderStatusText::className(), ['lang_id' => 'lang_id']);
	}

	/**
	 * Gets query for [[Pages]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getPages()
	{
		return $this->hasMany(Page::className(), ['lang_id' => 'lang_id']);
	}

	/**
	 * Gets query for [[PaymentMethodTexts]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getPaymentMethodTexts()
	{
		return $this->hasMany(PaymentMethodText::className(), ['lang_id' => 'lang_id']);
	}

	/**
	 * Gets query for [[PaymentMethods]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getPaymentMethods()
	{
		return $this->hasMany(PaymentMethod::className(), ['payment_method_id' => 'payment_method_id'])
			->viaTable('payment_method_text', ['lang_id' => 'lang_id']);
	}

	/**
	 * Gets query for [[PriceTexts]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getPriceTexts()
	{
		return $this->hasMany(PriceText::className(), ['lang_id' => 'lang_id']);
	}

	/**
	 * Gets query for [[ProductImageTexts]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getProductImageTexts()
	{
		return $this->hasMany(ProductImageText::className(), ['lang_id' => 'lang_id']);
	}

	/**
	 * Gets query for [[ProductImages]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getProductImages()
	{
		return $this->hasMany(ProductImage::className(), ['product_image_id' => 'product_image_id'])
			->viaTable('product_image_text', ['lang_id' => 'lang_id']);
	}

	/**
	 * Gets query for [[ProductImports]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getProductImports()
	{
		return $this->hasMany(ProductImport::className(), ['lang_id' => 'lang_id']);
	}

	/**
	 * Gets query for [[ProductTexts]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getProductTexts()
	{
		return $this->hasMany(ProductText::className(), ['lang_id' => 'lang_id']);
	}

	/**
	 * Gets query for [[Products]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getProducts()
	{
		return $this->hasMany(Product::className(), ['product_id' => 'product_id'])
			->viaTable('product_text', ['lang_id' => 'lang_id']);
	}

	/**
	 * Gets query for [[ServiceTexts]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getServiceTexts()
	{
		return $this->hasMany(ServiceText::className(), ['lang_id' => 'lang_id']);
	}

	/**
	 * Gets query for [[Services]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getServices()
	{
		return $this->hasMany(Service::className(), ['service_id' => 'service_id'])
			->viaTable('service_text', ['lang_id' => 'lang_id']);
	}

	/**
	 * Gets query for [[SiteCountryLangs]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getSiteCountryLangs()
	{
		return $this->hasMany(SiteCountryLang::className(), ['lang_id' => 'lang_id']);
	}

	/**
	 * Gets query for [[Sites]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getSites()
	{
		return $this->hasMany(Site::className(), ['site_id' => 'site_id'])
			->viaTable('instagram_config', ['lang_id' => 'lang_id']);
	}

	/**
	 * Gets query for [[SmsTemplates]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getSmsTemplates()
	{
		return $this->hasMany(SmsTemplate::className(), ['lang_id' => 'lang_id']);
	}

	/**
	 * Gets query for [[Statuses]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getStatuses()
	{
		return $this->hasMany(OrderStatus::className(), ['status_id' => 'status_id'])
			->viaTable('order_status_text', ['lang_id' => 'lang_id']);
	}

	/**
	 * Gets query for [[ThemeInstalledTexts]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getThemeInstalledTexts()
	{
		return $this->hasMany(ThemeInstalledText::className(), ['lang_id' => 'lang_id']);
	}

	/**
	 * Gets query for [[Values]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getValues()
	{
		return $this->hasMany(CharacteristicProductVal::className(), ['value_id' => 'value_id'])
			->viaTable('characteristic_product_val_text', ['lang_id' => 'lang_id']);
	}

	/**
	 * Gets query for [[Values0]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getValues0()
	{
		return $this->hasMany(CharacteristicVariantVal::className(), ['value_id' => 'value_id'])
			->viaTable('characteristic_variant_val_text', ['lang_id' => 'lang_id']);
	}

	/**
	 * Gets query for [[VariantTexts]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getVariantTexts()
	{
		return $this->hasMany(VariantText::className(), ['lang_id' => 'lang_id']);
	}

	/**
	 * Gets query for [[Variants]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getVariants()
	{
		return $this->hasMany(Variant::className(), ['variant_id' => 'variant_id'])
			->viaTable('variant_text', ['lang_id' => 'lang_id']);
	}

	/**
	 * Gets query for [[WarehouseTexts]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getWarehouseTexts()
	{
		return $this->hasMany(WarehouseText::className(), ['lang_id' => 'lang_id']);
	}

	/**
	 * Gets query for [[Warehouses]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getWarehouses()
	{
		return $this->hasMany(Warehouse::className(), ['warehouse_id' => 'warehouse_id'])
			->viaTable('warehouse_text', ['lang_id' => 'lang_id']);
	}
}
