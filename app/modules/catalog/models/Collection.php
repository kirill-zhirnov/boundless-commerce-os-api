<?php

namespace app\modules\catalog\models;

use Yii;
use app\modules\system\models\Lang;
use app\modules\system\models\Site;

/**
 * This is the model class for table "collection".
 *
 * @property int $collection_id
 * @property int $site_id
 * @property int $lang_id
 * @property string|null $title
 * @property string|null $alias
 * @property string $created_at
 * @property string|null $deleted_at
 *
 * @property CollectionProductRel[] $collectionProductRels
 * @property Lang $lang
 * @property Product[] $products
 * @property Site $site
 */
class Collection extends \yii\db\ActiveRecord
{
	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'collection';
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
			[['site_id', 'lang_id'], 'required'],
			[['site_id', 'lang_id'], 'default', 'value' => null],
			[['site_id', 'lang_id'], 'integer'],
			[['title', 'alias'], 'string'],
			[['created_at', 'deleted_at'], 'safe'],
			[['site_id', 'lang_id', 'alias'], 'unique', 'targetAttribute' => ['site_id', 'lang_id', 'alias']]
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'collection_id' => 'Collection ID',
			'site_id' => 'Site ID',
			'lang_id' => 'Lang ID',
			'title' => 'Title',
			'alias' => 'Alias',
			'created_at' => 'Created At',
			'deleted_at' => 'Deleted At',
		];
	}

	/**
	 * Gets query for [[CollectionProductRels]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getCollectionProductRels()
	{
		return $this->hasMany(CollectionProductRel::class, ['collection_id' => 'collection_id']);
	}

	/**
	 * Gets query for [[Lang]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getLang()
	{
		return $this->hasOne(Lang::class, ['lang_id' => 'lang_id']);
	}

	/**
	 * Gets query for [[Products]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getProducts()
	{
		return $this->hasMany(Product::class, ['product_id' => 'product_id'])->viaTable('collection_product_rel', ['collection_id' => 'collection_id']);
	}

	/**
	 * Gets query for [[Site]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getSite()
	{
		return $this->hasOne(Site::class, ['site_id' => 'site_id']);
	}
}
