<?php

namespace app\modules\catalog\models;

use Yii;
use app\modules\system\models\Lang;

/**
 * This is the model class for table "label".
 *
 * @property int $label_id
 * @property string $color
 * @property string $text_color
 * @property string|null $icon
 * @property int|null $remove_after
 * @property string $created_at
 * @property string|null $deleted_at
 *
 * @property LabelText[] $labelTexts
 * @property Lang[] $langs
 * @property ProductLabelRel[] $productLabelRels
 * @property Product[] $products
 */
class Label extends \yii\db\ActiveRecord
{
	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'label';
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
			[['color'], 'required'],
			[['remove_after'], 'default', 'value' => null],
			[['remove_after'], 'integer'],
			[['created_at', 'deleted_at'], 'safe'],
			[['color', 'text_color'], 'string', 'max' => 7],
			[['icon'], 'string', 'max' => 20],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'label_id' => 'Label ID',
			'color' => 'Color',
			'text_color' => 'Text Color',
			'icon' => 'Icon',
			'remove_after' => 'Remove After',
			'created_at' => 'Created At',
			'deleted_at' => 'Deleted At',
		];
	}

	/**
	 * Gets query for [[LabelTexts]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getLabelTexts()
	{
		return $this->hasMany(LabelText::class, ['label_id' => 'label_id']);
	}

	/**
	 * Gets query for [[Langs]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getLangs()
	{
		return $this->hasMany(Lang::class, ['lang_id' => 'lang_id'])->viaTable('label_text', ['label_id' => 'label_id']);
	}

	/**
	 * Gets query for [[ProductLabelRels]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getProductLabelRels()
	{
		return $this->hasMany(ProductLabelRel::class, ['label_id' => 'label_id']);
	}

	/**
	 * Gets query for [[Products]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getProducts()
	{
		return $this->hasMany(Product::class, ['product_id' => 'product_id'])->viaTable('product_label_rel', ['label_id' => 'label_id']);
	}

	public function fields()
	{
		$out = parent::fields();

		if ($this->isRelationPopulated('labelTexts') && $this->labelTexts) {
			$out['title'] = function () {
				return $this->labelTexts[0]?->title;
			};
		}

		return $out;
	}
}
