<?php

namespace app\modules\cms\models;

use app\modules\catalog\models\Category;
use app\modules\system\models\Site;
use Yii;

/**
 * This is the model class for table "menu_block".
 *
 * @property int $block_id
 * @property int $site_id
 * @property string $key
 * @property string $created_at
 *
 * @property Category[] $categories
 * @property CategoryMenuRel[] $categoryMenuRels
 * @property MenuItem[] $menuItems
 * @property Site $site
 */
class MenuBlock extends \yii\db\ActiveRecord
{
	const KEY_CATEGORY = 'category';

	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'menu_block';
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
			[['site_id', 'key'], 'required'],
			[['site_id'], 'default', 'value' => null],
			[['site_id'], 'integer'],
			[['created_at'], 'safe'],
			[['key'], 'string', 'max' => 100],
			[['site_id', 'key'], 'unique', 'targetAttribute' => ['site_id', 'key']],
			[['site_id'], 'exist', 'skipOnError' => true, 'targetClass' => Site::class, 'targetAttribute' => ['site_id' => 'site_id']],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'block_id' => 'Block ID',
			'site_id' => 'Site ID',
			'key' => 'Key',
			'created_at' => 'Created At',
		];
	}

	/**
	 * Gets query for [[Categories]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getCategories()
	{
		return $this->hasMany(Category::class, ['category_id' => 'category_id'])->viaTable('category_menu_rel', ['block_id' => 'block_id']);
	}

	/**
	 * Gets query for [[CategoryMenuRels]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getCategoryMenuRels()
	{
		return $this->hasMany(CategoryMenuRel::class, ['block_id' => 'block_id']);
	}

	/**
	 * Gets query for [[MenuItems]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getMenuItems()
	{
		return $this->hasMany(MenuItem::class, ['block_id' => 'block_id']);
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
