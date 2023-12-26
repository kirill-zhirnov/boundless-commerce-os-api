<?php

namespace app\modules\catalog\models;

use Yii;

/**
 * This is the model class for table "manufacturer_text".
 *
 * @property int $manufacturer_id
 * @property int $lang_id
 * @property string|null $title
 * @property int|null $typearea_id
 * @property string|null $custom_title
 * @property string|null $custom_header
 * @property string|null $meta_description
 * @property string|null $meta_keywords
 * @property string|null $url_key
 *
 * @property Lang $lang
 * @property Manufacturer $manufacturer
 */
class ManufacturerText extends \yii\db\ActiveRecord
{
	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'manufacturer_text';
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
			[['manufacturer_id', 'lang_id'], 'required'],
			[['manufacturer_id', 'lang_id', 'typearea_id'], 'default', 'value' => null],
			[['manufacturer_id', 'lang_id', 'typearea_id'], 'integer'],
			[['title', 'custom_title', 'custom_header', 'meta_description', 'meta_keywords', 'url_key'], 'string'],
			[['lang_id', 'url_key'], 'unique', 'targetAttribute' => ['lang_id', 'url_key']],
			[['manufacturer_id', 'lang_id'], 'unique', 'targetAttribute' => ['manufacturer_id', 'lang_id']],
			[['lang_id'], 'exist', 'skipOnError' => true, 'targetClass' => Lang::className(), 'targetAttribute' => ['lang_id' => 'lang_id']],
			[['manufacturer_id'], 'exist', 'skipOnError' => true, 'targetClass' => Manufacturer::className(), 'targetAttribute' => ['manufacturer_id' => 'manufacturer_id']],
			[['typearea_id'], 'exist', 'skipOnError' => true, 'targetClass' => Typearea::className(), 'targetAttribute' => ['typearea_id' => 'typearea_id']],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'manufacturer_id' => 'Manufacturer ID',
			'lang_id' => 'Lang ID',
			'title' => 'Title',
			'typearea_id' => 'Typearea ID',
			'custom_title' => 'Custom Title',
			'custom_header' => 'Custom Header',
			'meta_description' => 'Meta Description',
			'meta_keywords' => 'Meta Keywords',
			'url_key' => 'Url Key',
		];
	}

	/**
	 * Gets query for [[Lang]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getLang()
	{
		return $this->hasOne(Lang::className(), ['lang_id' => 'lang_id']);
	}

	/**
	 * Gets query for [[Manufacturer]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getManufacturer()
	{
		return $this->hasOne(Manufacturer::class, ['manufacturer_id' => 'manufacturer_id']);
	}

	public function fields(): array
	{
		$out = parent::fields();
		unset($out['meta_keywords'], $out['lang_id'], $out['custom_header'], $out['manufacturer_id'], $out['title'], $out['url_key']);

		return $out;
	}
}
