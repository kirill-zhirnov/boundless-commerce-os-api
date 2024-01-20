<?php

namespace app\modules\user\models;

use app\modules\user\activeQueries\PersonQuery;
use app\modules\user\components\CustomerTokenCreator;
use Yii;
use app\modules\system\models\Site;
use app\modules\orders\models\Basket;
use app\modules\orders\models\Orders;

/**
 * This is the model class for table "person".
 *
 * @property int $person_id
 * @property int $site_id
 * @property string|null $email
 * @property string|null $registered_at
 * @property string $created_at
 * @property string|null $deleted_at
 * @property bool $is_owner
 * @property string $status
 * @property int|null $created_by
 * @property string|null $public_id
 *
 * @property AdminComment[] $adminComments
 * @property Basket[] $baskets
 * @property Person $createdBy
 * @property InventoryMovement[] $inventoryMovements
 * @property OrderHistory[] $orderHistories
 * @property Orders[] $orders
 * @property PaymentTransaction[] $paymentTransactions
 * @property PersonAddress[] $personAddresses
 * @property PersonAuth $personAuth
 * @property PersonProfile $personProfile
 * @property PersonRoleRel[] $personRoleRels
 * @property PersonSearch $personSearch
 * @property PersonSettings $personSettings
 * @property PersonToken[] $personTokens
 * @property PersonVisitor $personVisitor
 * @property ProductImport[] $productImports
 * @property ProductReview[] $productReviews
 * @property Role[] $roles
 * @property Site $site
 * @property CustomerGroup[] $customerGroups
 */
class Person extends \yii\db\ActiveRecord
{
	const STATUS_PUBLISHED = 'published';

	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'person';
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
			[['site_id'], 'required'],
			[['site_id', 'created_by'], 'default', 'value' => null],
			[['site_id', 'created_by'], 'integer'],
			[['registered_at', 'created_at', 'deleted_at'], 'safe'],
			[['is_owner'], 'boolean'],
			[['status', 'public_id'], 'string'],
			[['email'], 'string', 'max' => 255],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'person_id' => 'Person ID',
			'site_id' => 'Site ID',
			'email' => 'Email',
			'registered_at' => 'Registered At',
			'created_at' => 'Created At',
			'deleted_at' => 'Deleted At',
			'is_owner' => 'Is Owner',
			'status' => 'Status',
			'created_by' => 'Created By',
			'public_id' => 'Public ID',
		];
	}

	/**
	 * Gets query for [[AdminComments]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getAdminComments()
	{
		return $this->hasMany(AdminComment::className(), ['person_id' => 'person_id']);
	}

	/**
	 * Gets query for [[Baskets]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getBaskets()
	{
		return $this->hasMany(Basket::class, ['person_id' => 'person_id']);
	}

	/**
	 * Gets query for [[CreatedBy]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getCreatedBy()
	{
		return $this->hasOne(Person::class, ['person_id' => 'created_by']);
	}

	/**
	 * Gets query for [[InventoryMovements]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getInventoryMovements()
	{
		return $this->hasMany(InventoryMovement::className(), ['person_id' => 'person_id']);
	}

	/**
	 * Gets query for [[OrderHistories]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getOrderHistories()
	{
		return $this->hasMany(OrderHistory::className(), ['person_id' => 'person_id']);
	}

	/**
	 * Gets query for [[Orders]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getOrders()
	{
		return $this->hasMany(Orders::class, ['customer_id' => 'person_id']);
	}

	/**
	 * Gets query for [[PaymentTransactions]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getPaymentTransactions()
	{
		return $this->hasMany(PaymentTransaction::className(), ['person_id' => 'person_id']);
	}

	/**
	 * Gets query for [[PersonAddresses]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getPersonAddresses()
	{
		return $this->hasMany(PersonAddress::class, ['person_id' => 'person_id']);
	}

	/**
	 * Gets query for [[PersonAuth]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getPersonAuth()
	{
		return $this->hasOne(PersonAuth::class, ['person_id' => 'person_id']);
	}

	/**
	 * Gets query for [[PersonProfile]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getPersonProfile()
	{
		return $this->hasOne(PersonProfile::class, ['person_id' => 'person_id']);
	}

	/**
	 * Gets query for [[PersonRoleRels]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getPersonRoleRels()
	{
		return $this->hasMany(PersonRoleRel::class, ['person_id' => 'person_id']);
	}

	/**
	 * Gets query for [[PersonSearch]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getPersonSearch()
	{
		return $this->hasOne(PersonSearch::className(), ['person_id' => 'person_id']);
	}

	/**
	 * Gets query for [[PersonSettings]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getPersonSettings()
	{
		return $this->hasOne(PersonSettings::className(), ['person_id' => 'person_id']);
	}

	/**
	 * Gets query for [[PersonTokens]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getPersonTokens()
	{
		return $this->hasMany(PersonToken::className(), ['person_id' => 'person_id']);
	}

	/**
	 * Gets query for [[PersonVisitor]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getPersonVisitor()
	{
		return $this->hasOne(PersonVisitor::className(), ['person_id' => 'person_id']);
	}

	/**
	 * Gets query for [[ProductReviews]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getProductReviews()
	{
		return $this->hasMany(ProductReview::className(), ['created_by' => 'person_id']);
	}

	/**
	 * Gets query for [[Roles]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getRoles()
	{
		return $this->hasMany(Role::class, ['role_id' => 'role_id'])->viaTable('person_role_rel', ['person_id' => 'person_id']);
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

	public function getCustomerGroups()
	{
		return $this->hasMany(CustomerGroup::class, ['group_id' => 'group_id'])
			->viaTable('person_group_rel', ['person_id' => 'person_id']);
	}

	public function createAuthToken(): string
	{
		$creator = new CustomerTokenCreator($this);
		return $creator->create();
	}

	public function checkDefaultAddressExists()
	{
		$total = self::getDb()
			->createCommand("select count(address_id) from person_address where person_id = :id and is_default is true")
			->bindValues(['id' => $this->person_id])
			->queryScalar()
		;

		if (!$total) {
			self::getDb()
				->createCommand("
					update person_address set is_default = true where address_id in (
						select address_id from person_address where person_id = :id limit 1
					)
				")
				->bindValues(['id' => $this->person_id])
				->execute()
			;
		}
	}

//	public static function findOrCreateGuestBuyer(string $email): Person
//	{
//		self::getDb()->createCommand("
//			insert into person
//				(site_id, email)
//			values
//				(:site, :email)
//			on conflict do nothing
//		")
//			->bindValues([
//				'site' => Site::DEFAULT_SITE,
//				'email' => $email
//			])
//			->execute()
//		;
//
//		/** @var Person $person */
//		$person = Person::find()
//			->where([
//				'site_id' => Site::DEFAULT_SITE,
//				'email' => $email
//			])
//			->one()
//		;
//
//		$person->bindRoles([Role::ALIAS_GUEST, Role::ALIAS_GUEST_BUYER]);
//
//		return $person;
//	}

	public function bindRoles(array $aliases)
	{
		$aliases = array_map(function ($val) {
			return $this->getDb()->quoteValue($val);
		}, $aliases);

		$this->getDb()->createCommand("
			insert into person_role_rel
					(person_id, role_id)
				select
					:person,
					role_id
				from
					role
				where
					alias in (" . implode(', ', $aliases) . ")
				on conflict
					do nothing
		")
			->bindValues(['person' => $this->person_id])
			->execute()
		;
	}

	public function rmRolesNotIn(array $aliases)
	{
		$aliases = array_map(function ($val) {
			return $this->getDb()->quoteValue($val);
		}, $aliases);

		$this->getDb()->createCommand("
			delete from
				person_role_rel
			where
				person_id = :person
				and role_id in (
					select
						role_id
					from
						role
					where
						alias not in (" . implode(', ', $aliases) . ")
				)
		")
			->bindValues(['person' => $this->person_id])
			->execute()
		;
	}

	public function rmRolesIn(array $aliases)
	{
		$aliases = array_map(function ($val) {
			return $this->getDb()->quoteValue($val);
		}, $aliases);

		$this->getDb()->createCommand("
			delete from
				person_role_rel
			where
				person_id = :person
				and role_id in (
					select
						role_id
					from
						role
					where
						alias in (" . implode(', ', $aliases) . ")
				)
		")
			->bindValues(['person' => $this->person_id])
			->execute()
		;
	}

	public function setGuestBuyerRoles()
	{
		$aliases = [Role::ALIAS_GUEST, Role::ALIAS_GUEST_BUYER];
		$this->bindRoles($aliases);
		$this->rmRolesNotIn($aliases);
	}

	public function addClientRoles()
	{
		$aliases = [Role::ALIAS_GUEST, Role::ALIAS_CLIENT];
		$this->bindRoles($aliases);

		$this->rmRolesIn([Role::ALIAS_GUEST_BUYER]);
	}

	public function setClientRoles()
	{
		$aliases = [Role::ALIAS_GUEST, Role::ALIAS_CLIENT];
		$this->bindRoles($aliases);
		$this->rmRolesNotIn($aliases);
	}

	public static function find(): PersonQuery
	{
		return new PersonQuery(get_called_class());
	}

	public function findDefaultShippingAddress(): null|PersonAddress
	{
		return $this->findDefaultAddress(PersonAddress::TYPE_SHIPPING);
	}

	public function findDefaultBillingAddress(): null|PersonAddress
	{
		return $this->findDefaultAddress(PersonAddress::TYPE_BILLING);
	}

	public function findShippingAddress(): null|PersonAddress
	{
		return $this->findAddressByType(PersonAddress::TYPE_SHIPPING);
	}

	public function findBillingAddress(): null|PersonAddress
	{
		return $this->findAddressByType(PersonAddress::TYPE_BILLING);
	}

	public function findAddressByType($type): null|PersonAddress
	{
		return PersonAddress::find()
			->where([
				'person_id' => $this->person_id,
				'type' => $type
			])
			->one()
		;
	}

	public function findDefaultAddress($type): null|PersonAddress
	{
		$defaultAddress = null;
		$addressByType = null;

		/** @var PersonAddress[] $addresses */
		$addresses = PersonAddress::find()
			->where('person_id = :person and (is_default is true or "type" = :type)', [
				'person' => $this->person_id,
				'type' => $type
			])
			->all()
		;

		foreach ($addresses as $address) {
			if ($address->is_default) {
				$defaultAddress = $address;
			} elseif ($address->type == $type) {
				$addressByType = $address;
			}
		}

		$outAddress = null;
		if ($addressByType) {
			$outAddress = $addressByType;
		} elseif ($defaultAddress) {
			$outAddress = $defaultAddress;
		}

		return $outAddress;
	}

	public function fillNamesByAddress(PersonAddress $address, $forceNames = false)
	{
		$profile = $this->personProfile;
		if ((!$profile->first_name && !$profile->last_name) || $forceNames) {
			$profile->first_name = $address->first_name;
			$profile->last_name = $address->last_name;
			$profile->save(false);
		}
	}

	public static function createGuestBuyer(): self
	{
		$person = new Person();
		$person->site_id = Site::DEFAULT_SITE;
		$person->save(false);
		$person->setGuestBuyerRoles();

		return $person;
	}

	public function assignGroup(CustomerGroup $group)
	{
		self::getDb()->createCommand("
			insert into person_group_rel
				(person_id, group_id)
			values
				(:person, :group)
			on conflict do nothing
		")
			->bindValues([
				'person' => $this->person_id,
				'group' => $group->group_id
			])
			->execute()
		;
	}

	public function fields(): array
	{
		$out = [
			'id' => function ($model) {
				return $model->public_id;
			},
			'email',
			'created_at'
		];

		if ($this->isRelationPopulated('personProfile')) {
			$out['first_name'] = function (self $model) {
				return $model->personProfile->first_name;
			};
			$out['last_name'] = function (self $model) {
				return $model->personProfile->last_name;
			};
			$out['phone'] = function (self $model) {
				return $model->personProfile->phone;
			};
			$out['receive_marketing_info'] = function (self $model) {
				return $model->personProfile->receive_marketing_info;
			};
			$out['custom_attrs'] = function (self $model) {
				return $model->personProfile->custom_attrs;
			};
		}

		if ($this->isRelationPopulated('personAddresses')) {
			$out['addresses'] = function (self $model) {
				return $model->personAddresses;
			};
		}

		if ($this->isRelationPopulated('customerGroups')) {
			$out['groups'] = fn (self $model) => $model->customerGroups;
		}

		return $out;
	}
}
