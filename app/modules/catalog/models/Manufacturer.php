<?php

namespace app\modules\catalog\models;

use Yii;
use app\modules\system\models\Lang;
use app\modules\cms\models\Image;

/**
 * This is the model class for table "manufacturer".
 *
 * @property int $manufacturer_id
 * @property string $created_at
 * @property string|null $layout
 * @property string|null $deleted_at
 * @property int|null $image_id
 *
 * @property Image $image
 * @property Lang[] $langs
 * @property ManufacturerText[] $manufacturerTexts
 * @property Product[] $products
 */
class Manufacturer extends \yii\db\ActiveRecord
{
	const STATUS_PUBLISHED = 'published';

	public $products_qty;

	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'manufacturer';
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
			[['created_at', 'deleted_at'], 'safe'],
			[['image_id'], 'default', 'value' => null],
			[['image_id'], 'integer'],
			[['layout'], 'string', 'max' => 255],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'manufacturer_id' => 'Manufacturer ID',
			'created_at' => 'Created At',
			'layout' => 'Layout',
			'deleted_at' => 'Deleted At',
			'image_id' => 'Image ID',
		];
	}

	/**
	 * Gets query for [[Image]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getImage()
	{
		return $this->hasOne(Image::class, ['image_id' => 'image_id']);
	}

	/**
	 * Gets query for [[Langs]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getLangs()
	{
		return $this->hasMany(Lang::class, ['lang_id' => 'lang_id'])->viaTable('manufacturer_text', ['manufacturer_id' => 'manufacturer_id']);
	}

	/**
	 * Gets query for [[ManufacturerTexts]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getManufacturerTexts()
	{
		return $this->hasMany(ManufacturerText::class, ['manufacturer_id' => 'manufacturer_id']);
	}

	/**
	 * Gets query for [[Products]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getProducts()
	{
		return $this->hasMany(Product::class, ['manufacturer_id' => 'manufacturer_id']);
	}

	public function fields()
	{
		$out = ['manufacturer_id'];
		$out['title'] = fn () => $this->manufacturerTexts[0]->title;
		$out['url_key'] = fn () => $this->manufacturerTexts[0]->url_key;

		$out['text'] = function () {
			return $this->manufacturerTexts[0];
		};

		$out['image'] = function () {
			if ($this->isRelationPopulated('image') && $this->image) {
				return $this->image;
			}
		};

		if (isset($this->products_qty)) {
			$out['products_qty'] = function (self $model) {
				return $model->products_qty;
			};
		}

		$out[] = 'status';
		$out[] = 'created_by';
		$out[] = 'created_at';

		return $out;
	}
}
