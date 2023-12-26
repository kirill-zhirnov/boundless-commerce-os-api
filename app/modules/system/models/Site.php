<?php

namespace app\modules\system\models;

use Yii;

/**
 * This is the model class for table "site".
 *
 * @property int $site_id
 * @property string|null $host
 * @property string|null $settings
 * @property string|null $aliases
 * @property string|null $system_host
 *
 * @property Article[] $articles
 * @property Category[] $categories
 * @property Collection[] $collections
 * @property Delivery[] $deliveries
 * @property DeliverySite[] $deliverySites
 * @property Image[] $images
 * @property Import[] $imports
 * @property InstagramConfig[] $instagramConfigs
 * @property Lang[] $langs
 * @property MenuBlock[] $menuBlocks
 * @property Page[] $pages
 * @property PaymentMethod[] $paymentMethods
 * @property Person[] $people
 * @property PointSale[] $pointSales
 * @property ProductImport[] $productImports
 * @property SiteCountryLang[] $siteCountryLangs
 */
class Site extends \yii\db\ActiveRecord
{
	const DEFAULT_SITE = 1;

	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'site';
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
			[['settings', 'aliases'], 'safe'],
			[['host', 'system_host'], 'string', 'max' => 100],
			[['host'], 'unique'],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'site_id' => 'Site ID',
			'host' => 'Host',
			'settings' => 'Settings',
			'aliases' => 'Aliases',
			'system_host' => 'System Host',
		];
	}

	/**
	 * Gets query for [[Articles]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getArticles()
	{
		return $this->hasMany(Article::className(), ['site_id' => 'site_id']);
	}

	/**
	 * Gets query for [[Categories]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getCategories()
	{
		return $this->hasMany(Category::className(), ['site_id' => 'site_id']);
	}

	/**
	 * Gets query for [[Collections]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getCollections()
	{
		return $this->hasMany(Collection::className(), ['site_id' => 'site_id']);
	}

	/**
	 * Gets query for [[Deliveries]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getDeliveries()
	{
		return $this->hasMany(Delivery::className(), ['delivery_id' => 'delivery_id'])
			->viaTable('delivery_site', ['site_id' => 'site_id']);
	}

	/**
	 * Gets query for [[DeliverySites]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getDeliverySites()
	{
		return $this->hasMany(DeliverySite::className(), ['site_id' => 'site_id']);
	}

	/**
	 * Gets query for [[Images]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getImages()
	{
		return $this->hasMany(Image::className(), ['site_id' => 'site_id']);
	}

	/**
	 * Gets query for [[Imports]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getImports()
	{
		return $this->hasMany(Import::className(), ['site_id' => 'site_id']);
	}

	/**
	 * Gets query for [[InstagramConfigs]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getInstagramConfigs()
	{
		return $this->hasMany(InstagramConfig::className(), ['site_id' => 'site_id']);
	}

	/**
	 * Gets query for [[Langs]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getLangs()
	{
		return $this->hasMany(Lang::className(), ['lang_id' => 'lang_id'])
			->viaTable('instagram_config', ['site_id' => 'site_id']);
	}

	/**
	 * Gets query for [[MenuBlocks]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getMenuBlocks()
	{
		return $this->hasMany(MenuBlock::className(), ['site_id' => 'site_id']);
	}

	/**
	 * Gets query for [[Pages]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getPages()
	{
		return $this->hasMany(Page::className(), ['site_id' => 'site_id']);
	}

	/**
	 * Gets query for [[PaymentMethods]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getPaymentMethods()
	{
		return $this->hasMany(PaymentMethod::className(), ['site_id' => 'site_id']);
	}

	/**
	 * Gets query for [[People]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getPeople()
	{
		return $this->hasMany(Person::className(), ['site_id' => 'site_id']);
	}

	/**
	 * Gets query for [[PointSales]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getPointSales()
	{
		return $this->hasMany(PointSale::className(), ['site_id' => 'site_id']);
	}

	/**
	 * Gets query for [[ProductImports]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getProductImports()
	{
		return $this->hasMany(ProductImport::className(), ['site_id' => 'site_id']);
	}

	/**
	 * Gets query for [[SiteCountryLangs]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getSiteCountryLangs()
	{
		return $this->hasMany(SiteCountryLang::className(), ['site_id' => 'site_id']);
	}
}
