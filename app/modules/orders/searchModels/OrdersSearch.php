<?php

namespace app\modules\orders\searchModels;

use app\modules\orders\models\AdminOrders;
use app\modules\orders\models\Orders;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\db\ActiveQueryInterface;
use yii\db\Expression;
use yii\validators\DateValidator;
use yii\validators\NumberValidator;
use yii\web\BadRequestHttpException;
use Yii;
use app\validators\UuidValidator;

class OrdersSearch extends Model
{
	public $created_at;

	public $status_id;

	public $total_price;

	public $customer_id;

	public function rules(): array
	{
		return [
			['created_at', 'validateCreatedAt'],
			['status_id', 'integer', 'min' => 0],
			['total_price', 'validateTotalPrice'],
			['customer_id', UuidValidator::class]
		];
	}

	public function search(array $params = []): ActiveDataProvider
	{
		$this->load($params, '');
		if (!$this->validate()) {
			throw new BadRequestHttpException(implode(' ', $this->getErrorSummary(false)));
		}

		$query = $this->makeQuery();
		$this->setupQueryBasics($query);
		$this->applyFilters($query);

		$dataProvider = new ActiveDataProvider([
			'query' => $query,
			'sort' => [
				'attributes' => [
					'created_at',
					'total_price'
				],
				'defaultOrder' => [
					'created_at' => SORT_DESC
				],
			],
		]);

		return $dataProvider;
	}

	public function validateCreatedAt()
	{
		$message = Yii::t('app', 'Valid format Y-m-d, e.g. 2022-01-02. Modifiers might be added at the beginning for the string: "<" or ">", e.g. >2022-01-02');
		if (!preg_match('/^(<|>)?(\d{4}-\d{2}-\d{2})$/', $this->created_at, $matches)) {
			$this->addError('created_at', $message);
			return;
		}

		$dateValidator = new DateValidator();
		$dateValidator->init();
		$dateValidator->format = 'yyyy-MM-dd';
		$dateValidator->min = strtotime('1900-01-01');
		$dateValidator->max = strtotime('2100-01-01');

		if (!$dateValidator->validate($matches[2])) {
			$this->addError('created_at', $message . " Min value: 1900-01-01, Max: 2100-01-01.");
			return;
		}
	}

	public function validateTotalPrice()
	{
		if (!preg_match('/^(<|>)?(.*)$/', $this->total_price, $matches)) {
			return;
		}

		$numberValidators = new NumberValidator();
		$numberValidators->init();
		$numberValidators->min = 0;

		if (!$numberValidators->validate($matches[2])) {
			$this->addError('total_price', 'Incorrect total price.');
			return;
		}
	}

	protected function makeQuery(): ActiveQueryInterface
	{
		return AdminOrders::find();
	}

	protected function setupQueryBasics(ActiveQueryInterface $query)
	{
		$query
			->joinWith('customer')
			->with(['customer.personProfile', 'customer.personAddresses.vwCountry'])
			->with(['orderDiscounts', 'orderProp'])
			->with(['paymentMethod.paymentMethodText', 'paymentMethod.paymentGateway'])
			->with(['status.statusText'])
			->with([
				'orderServices.orderServiceDelivery',
				'orderServices.itemPrice',
				'orderServices.orderServiceDelivery.delivery.vwShipping',
				'orderServices.orderServiceDelivery.delivery.deliveryText',
			])
            ->andWhere(['orders.publishing_status' => Orders::STATUS_PUBLISHED])
		;
	}

	protected function applyFilters(ActiveQueryInterface $query)
	{
		$query
			->andFilterWhere(['orders.status_id' => $this->status_id])
			->andFilterWhere(['person.public_id' => $this->customer_id])
			->andFilterCompare(new Expression('date(orders.created_at)'), $this->created_at)
			->andFilterCompare('orders.total_price', $this->total_price)
		;
	}
}
