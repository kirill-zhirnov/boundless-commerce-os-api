<?php

namespace app\modules\user\models;

use app\modules\user\components\CustomerTokenParser;
use yii\web\IdentityInterface;
use yii\base\BaseObject;

class CustomerUser extends BaseObject implements IdentityInterface
{
	protected Person $person;

	public static function findIdentityByAccessToken($token, $type = null): ?static
	{
		$tokenParser = new CustomerTokenParser($token);
		$publicId = $tokenParser->getId();

		if ($tokenParser->isValid() && $publicId) {
			/** @var Person $person */
			$person = Person::find()
				->publicPersonScope()
				->where([
					'public_id' => $publicId,
					'deleted_at' => null,
					'status' => Person::STATUS_PUBLISHED
				])
				->andWhere('
					exists (
						select 1
						from
							person_role_rel
							inner join role using(role_id)
						where
							role.alias in (:aliasClient, :aliasAdmin)
							and person_role_rel.person_id = person.person_id
					)
				', [
					'aliasClient' => Role::ALIAS_CLIENT,
					'aliasAdmin' => Role::ALIAS_ADMIN,
				])
				->one()
			;

			if ($person) {
				$user = new static();
				$user->setPerson($person);

				return $user;
			}
		}

		return null;
	}

	public function setPerson(Person $person): self
	{
		$this->person = $person;
		return $this;
	}

	public function getPerson(): Person|null
	{
		return $this->person ?? null;
	}

	public static function findIdentity($id)
	{
		return null;
	}

	public function getId()
	{
		return null;
	}

	public function getAuthKey()
	{
		return null;
	}

	public function validateAuthKey($authKey)
	{
		throw new \RuntimeException('Not supportable method');
	}
}
