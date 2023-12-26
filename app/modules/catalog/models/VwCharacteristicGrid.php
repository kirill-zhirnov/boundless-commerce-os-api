<?php

namespace app\modules\catalog\models;

use app\modules\catalog\activeQueries\VwCharacteristicGridQuery;
use Yii;

/**
 * This is the model class for table "vw_characteristic_grid".
 *
 * @property int|null $characteristic_id
 * @property int|null $parent_id
 * @property int|null $lang_id
 * @property int|null $group_id
 * @property string|null $title
 * @property string|null $joined_title
 * @property string|null $parent_title
 * @property string|null $alias
 * @property string|null $type
 * @property string|null $system_type
 * @property bool|null $is_folder
 * @property bool|null $is_hidden
 * @property string|null $default_value
 * @property string|null $help
 * @property int|null $level
 * @property int|null $sort
 * @property string|null $tree_sort
 */
class VwCharacteristicGrid extends \yii\db\ActiveRecord
{
	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'vw_characteristic_grid';
	}

	/**
	 * @return \yii\db\Connection the database connection used by this AR class.
	 */
	public static function getDb()
	{
		return Yii::$app->get('instanceDb');
	}

	public static function primaryKey()
	{
		return ['characteristic_id'];
	}

	/**
	 * {@inheritdoc}
	 */
	public function rules()
	{
		return [
			[['characteristic_id', 'parent_id', 'lang_id', 'group_id', 'level', 'sort'], 'default', 'value' => null],
			[['characteristic_id', 'parent_id', 'lang_id', 'group_id', 'level', 'sort'], 'integer'],
			[['title', 'joined_title', 'parent_title', 'alias', 'type', 'system_type', 'default_value', 'help', 'tree_sort'], 'string'],
			[['is_folder', 'is_hidden'], 'boolean'],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'characteristic_id' => 'Characteristic ID',
			'parent_id' => 'Parent ID',
			'lang_id' => 'Lang ID',
			'group_id' => 'Group ID',
			'title' => 'Title',
			'joined_title' => 'Joined Title',
			'parent_title' => 'Parent Title',
			'alias' => 'Alias',
			'type' => 'Type',
			'system_type' => 'System Type',
			'is_folder' => 'Is Folder',
			'is_hidden' => 'Is Hidden',
			'default_value' => 'Default Value',
			'help' => 'Help',
			'level' => 'Level',
			'sort' => 'Sort',
			'tree_sort' => 'Tree Sort',
		];
	}

	public function fields(): array
	{
		$fields = array_merge(
			[
				'id' => fn (self $model) => $model->characteristic_id
			],
			parent::fields()
		);
		unset($fields['system_type'], $fields['is_hidden'], $fields['default_value'], $fields['lang_id'], $fields['characteristic_id']);

		if ($this->isRelationPopulated('characteristicTypeCases')) {
			$fields['cases'] = function (self $model) {
				return $model->characteristicTypeCases;
			};
		}

		return $fields;
	}

	public static function find(): VwCharacteristicGridQuery
	{
		return new VwCharacteristicGridQuery(get_called_class());
	}

	public function getCharacteristicTypeCases()
	{
		return $this->hasMany(CharacteristicTypeCase::class, ['characteristic_id' => 'characteristic_id']);
	}
}
