<?php

namespace app\modules\system\models;

use app\modules\user\models\User;
use Yii;
use yii\caching\Cache;

/**
 * This is the model class for table "setting".
 *
 * @property int $setting_id
 * @property string $setting_group
 * @property string $key
 * @property string|null $value
 */
class Setting extends \yii\db\ActiveRecord
{
	const GROUP_INVENTORY = 'inventory';

	const KEY_TRACK_INVENTORY = 'trackInventory';

	const GROUP_ORDERS = 'orders';

	const KEY_CHECKOUT_PAGE = 'checkoutPage';

	const GROUP_SYSTEM = 'system';

	const KEY_CUSTOMER_JWT_SECRET = 'customerJWTSecret';

	const KEY_NEW_ORDER_STATUS_ID = 'new_order_status_id';

	const KEY_CURRENCY = 'currency';

	const KEY_SEO_TEMPLATES = 'seoTemplates';

	const GROUP_CMS = 'cms';

	const KEY_WIX_SITE_DRAFT_SETTINGS = 'wixSiteDraftSettings';

	const KEY_WIX_SITE_LIVE_SETTINGS = 'wixSiteLiveSettings';

	const KEY_TAX = 'tax';

	const KEY_MIN_ORDER_AMOUNT = 'minOrderAmount';

	const KEY_LOCALE = 'locale';

	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'setting';
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
			[['setting_group', 'key'], 'required'],
			[['setting_group', 'key'], 'string'],
			[['value'], 'safe'],
			[['key', 'setting_group'], 'unique', 'targetAttribute' => ['key', 'setting_group']],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'setting_id' => 'Setting ID',
			'setting_group' => 'Setting Group',
			'key' => 'Key',
			'value' => 'Value',
		];
	}

	/**
	 * @param string $group
	 * @param string $key
	 * @return mixed
	 * @throws \yii\db\Exception
	 */
	public static function getSetting(string $group, string $key)
	{
		$value = static::getDb()->createCommand("select value from setting where setting_group = :group and key = :key")
			->bindValues([
				'group' => $group,
				'key' => $key
			])
			->queryScalar()
		;

		return json_decode($value, true);
	}

	public static function setSetting(string $group, string $key, $value)
	{
		static::getDb()->createCommand("
			update setting set value = :value
			where
				setting_group = :group
				and key = :key
		")
			->bindValues([
				'group' => $group,
				'key' => $key,
				'value' => json_encode($value)
			])
			->execute()
		;

		/** @var Cache $cache */
		$cache = Yii::$app->cache;
		$cache->set(self::getCacheKey($group, $key), $value);
	}

	public static function getCachedSetting(string $group, string $key)
	{
		$cacheKey = self::getCacheKey($group, $key);

		/** @var Cache $cache */
		$cache = Yii::$app->cache;
		return $cache->getOrSet($cacheKey, function () use ($group, $key) {
			return self::getSetting($group, $key);
		});
	}

	public static function getCacheKey(string $group, string $key): string
	{
		$dbName = self::extractDbName();
		return "{$dbName}_settings_{$group}_{$key}";
	}

	public static function shallTrackInventory(): bool
	{
		return static::getCachedSetting(self::GROUP_INVENTORY, self::KEY_TRACK_INVENTORY);
	}

	public static function getCheckoutPage(): array
	{
		return static::getCachedSetting(self::GROUP_ORDERS, self::KEY_CHECKOUT_PAGE);
	}

	public static function getCustomerJWTSecret(): string|null
	{
		return static::getCachedSetting(self::GROUP_SYSTEM, self::KEY_CUSTOMER_JWT_SECRET);
	}

	public static function setCustomerJWTSecret($value)
	{
		self::setSetting(self::GROUP_SYSTEM, self::KEY_CUSTOMER_JWT_SECRET, $value);
	}

	public static function getNewOrderStatusId(): int
	{
		return self::getCachedSetting(self::GROUP_ORDERS, self::KEY_NEW_ORDER_STATUS_ID);
	}

	public static function getCurrencyAlias(): string
	{
		$value = self::getCachedSetting(self::GROUP_SYSTEM, self::KEY_CURRENCY);
		return $value['alias'];
	}

	public static function getCurrency(): Currency
	{
		return Currency::findOne(['alias' => self::getCurrencyAlias()]);
	}

	public static function getSeoTemplates(): array|null
	{
		return self::getCachedSetting(self::GROUP_SYSTEM, self::KEY_SEO_TEMPLATES);
	}

	public static function getSystemTax(): array
	{
		return self::getCachedSetting(self::GROUP_SYSTEM, self::KEY_TAX);
	}

	public static function getMinOrderAmount(): string|int|float
	{
		return self::getCachedSetting(self::GROUP_ORDERS, self::KEY_MIN_ORDER_AMOUNT);
	}

	public static function getLocaleSettings(): array
	{
		return self::getCachedSetting(self::GROUP_SYSTEM, self::KEY_LOCALE);
	}

	public static function extractDbName(): string
	{
		$db = static::getDb();
		if (preg_match('/dbname=([\w]+)/i', $db->dsn, $matches)) {
			return $matches[1];
		}

		throw new \RuntimeException('Cannot extract db name from DSN');
	}
}
