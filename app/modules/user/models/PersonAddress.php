<?php

namespace app\modules\user\models;

use app\helpers\Models;
use app\modules\delivery\models\VwCountry;
use app\modules\system\models\Lang;
use Yii;

/**
 * This is the model class for table "person_address".
 *
 * @property int $address_id
 * @property int $person_id
 * @property string|null $type
 * @property bool $is_default
 * @property string|null $first_name
 * @property string|null $last_name
 * @property string|null $company
 * @property string|null $address_line_1
 * @property string|null $address_line_2
 * @property string|null $city
 * @property string|null $state
 * @property int|null $country_id
 * @property string|null $zip
 * @property string|null $phone
 * @property string|null $comment
 * @property string $created_at
 * @property string $public_id
 *
 * @property Person $person
 * @property VwCountry $vwCountry
 */
class PersonAddress extends \yii\db\ActiveRecord
{
	const TYPE_SHIPPING = 'shipping';
	const TYPE_BILLING = 'billing';

	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'person_address';
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
			[['person_id'], 'required'],
			[['person_id', 'country_id'], 'default', 'value' => null],
			[['person_id', 'country_id'], 'integer'],
			[['type', 'comment'], 'string'],
			[['is_default'], 'boolean'],
			[['created_at'], 'safe'],
			[['first_name', 'last_name', 'city', 'state', 'zip', 'phone'], 'string', 'max' => 100],
			[['company'], 'string', 'max' => 200],
			[['address_line_1', 'address_line_2'], 'string', 'max' => 300],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'address_id' => 'Address ID',
			'person_id' => 'Person ID',
			'type' => 'Type',
			'is_default' => 'Is Default',
			'first_name' => 'First Name',
			'last_name' => 'Last Name',
			'company' => 'Company',
			'address_line_1' => 'Address Line 1',
			'address_line_2' => 'Address Line 2',
			'city' => 'City',
			'state' => 'State',
			'country_id' => 'Country ID',
			'zip' => 'Zip',
			'phone' => 'Phone',
			'comment' => 'Comment',
			'created_at' => 'Created At',
		];
	}

	public function beforeSave($insert): bool
	{
		if (!parent::beforeSave($insert)) {
			return false;
		}

		Models::emptyStr2Null($this, [
			'type', 'first_name', 'last_name', 'company', 'address_line_1', 'address_line_2',
			'city', 'state', 'zip', 'phone', 'comment'
		]);

		return true;
	}

	/**
	 * Gets query for [[Person]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getPerson()
	{
		return $this->hasOne(Person::class, ['person_id' => 'person_id']);
	}

	public function getVwCountry()
	{
		return $this->hasOne(VwCountry::class, ['country_id' => 'country_id'])
			->where(['vw_country.lang_id' => Lang::DEFAULT_LANG])
		;
	}

	public function isShippingType(): bool
	{
		return $this->type === self::TYPE_SHIPPING;
	}

	public function isBillingType(): bool
	{
		return $this->type === self::TYPE_BILLING;
	}

	public function isFilled(): bool
	{
		$requiredFields = ['address_line_1', 'city', 'country_id'];
		foreach ($requiredFields as $field) {
			if (empty($this->{$field})) {
				return false;
			}
		}

		return true;
	}

	public function getShortRepresentation(): string
	{
		$out = [];
		$fields = ['zip', 'address_line_1', 'city', 'state', 'country'];
		foreach ($fields as $field) {
			if ($field === 'country') {
				if ($this->vwCountry) {
					$out[] = $this->vwCountry->title;
				}
			} else {
				if (!empty($this->{$field})) {
					$out[] = $this->{$field};
				}
			}
		}

		return implode(', ', $out);
	}

	public function copyToType(string $type): self
	{
		$newType = self::find()->where([
			'person_id' => $this->person_id,
			'type' => $type
		])->one();

		if (!$newType) {
			$newType = new self();
			$newType->person_id = $this->person_id;
			$newType->type = $type;
		}

		$newType->attributes = [
			'first_name' => $this->first_name,
			'last_name' => $this->last_name,
			'company' => $this->company,
			'address_line_1' => $this->address_line_1,
			'address_line_2' => $this->address_line_2,
			'city' => $this->city,
			'state' => $this->state,
			'country_id' => $this->country_id,
			'zip' => $this->zip,
			'phone' => $this->phone,
			'comment' => $this->comment
		];
		$newType->save(false);
		return $newType;
	}

	public static function findOrCreateAddressByType(Person $person, string $type): self
	{
		$personAddress = self::find()
			->where([
				'person_id' => $person->person_id,
				'type' => $type
			])
			->one()
		;

		if ($personAddress) {
			return $personAddress;
		}

		$personAddress = new PersonAddress();
		$personAddress->attributes = [
			'person_id' => $person->person_id,
			'type' => $type
		];
		$personAddress->save(false);

		return $personAddress;
	}

	public function fields(): array
	{
		$out = [
			'id' => function (self $model) {
				return $model->public_id;
			},
			'type',
			'is_default',
			'first_name',
			'last_name',
			'company',
			'address_line_1',
			'address_line_2',
			'city',
			'state',
			'country_id',
			'zip',
			'phone',
			'created_at'
		];

		if (isset($this->vwCountry)) {
			$out['vwCountry'] = function (self $model) {
				return $model->vwCountry;
			};
		}

		return $out;
	}
}
