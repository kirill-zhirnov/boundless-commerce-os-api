<?php

namespace app\modules\orders\components;

use app\modules\catalog\models\ProductProp;
use app\modules\orders\models\CouponCampaign;
use app\modules\user\models\PersonAddress;
use yii\base\Model;
use app\modules\catalog\models\TaxClass;
use app\modules\catalog\models\TaxRate;

class TotalCalculator extends Model
{
	const CALCULATE_TAX_BASED_ON_STORE_LOCATION = 'storeLocation';
	const CALCULATE_TAX_BASED_ON_CUSTOMER_SHIPPING_ADDRESS = 'customerShippingAddress';
	const CALCULATE_TAX_BASED_ON_CUSTOMER_BILLING_ADDRESS = 'customerBillingAddress';

	const MODE_ADD_TO_TOTAL = 'addToTotal';
	const MODE_ALREADY_IN_TOTAL = 'alreadyInTotal';

	protected array $itemsList = [];

	protected array $shipping = [
		'price' => '0',
		'qty' => 0
	];

	protected array $servicesTotal = [
		'price' => '0',
		'qty' => 0
	];

	protected array $discounts = [];

	protected string $paymentMarkUp = '0';

	protected array $taxSettings = [
		'turnedOn' => false,
		'pricesEnteredWithTax' => false,
		'calculateTaxBasedOn' => 'storeLocation'
	];

	/**
	 * @var TaxClass[]
	 */
	protected array $taxClasses = [];

	protected PersonAddress|null $shippingLocation = null;
	protected PersonAddress|null $billingLocation = null;

	public function addItem(int $id, string|null $price, int $qty, string $taxStatus = 'none', int|null $taxClassId = null): self
	{
		$alreadyAdded = current(array_filter($this->itemsList, fn ($item) => $item['id'] === $id));

		if (!$alreadyAdded) {
			$this->itemsList[] = [
				'id' => $id,
				'price' => is_null($price) ? '0' : $price,
				'qty' => $qty,
				'taxStatus' => $taxStatus,
				'taxClassId' => $taxClassId
			];
		}

		return $this;
	}

	public function setShipping(string|null $price, int|null $qty): self
	{
		$this->shipping = [
			'price' => is_null($price) ? '0' : $price,
			'qty' => is_null($qty) ? 0 : $qty
		];

		return $this;
	}

	public function setServicesTotal(string|null $price, int|null $qty): self
	{
		$this->servicesTotal = [
			'price' => is_null($price) ? '0' : $price,
			'qty' => is_null($qty) ? 0 : $qty
		];

		return $this;
	}

	public function setPaymentMarkUp(string|null $value): self
	{
		$this->paymentMarkUp = is_null($value) ? '0' : $value;

		return $this;
	}

	public function addDiscount(string $type, string $value): self
	{
		if (!in_array($type, [CouponCampaign::DISCOUNT_TYPE_FIXED, CouponCampaign::DISCOUNT_TYPE_PERCENT])) {
			throw new \RuntimeException('Incorrect discount type: ' . $type);
		}

		$this->discounts[] = [
			'type' => $type,
			'value' => $value
		];

		return $this;
	}

	public function calcTotal(): array
	{
		$itemsTotal = $this->calcTotalForItems();

		$basicPrice = bcadd(
			bcadd($itemsTotal['price'], $this->shipping['price']),
			$this->servicesTotal['price']
		);

		$price = $basicPrice;
		$totalDiscount = '0';
		foreach ($this->discounts as $discount) {
			switch ($discount['type']) {
				case CouponCampaign::DISCOUNT_TYPE_FIXED:
					$totalDiscount = bcadd($totalDiscount, $discount['value']);
					$price = bcsub($price, $discount['value']);
					break;

				case CouponCampaign::DISCOUNT_TYPE_PERCENT:
					//I'm using round - because JS moolah and bc-math functions round differently
					$rowVal = round(bcmul(bcdiv($discount['value'], 100), $itemsTotal['price'], 6), 2);

					$totalDiscount = bcadd($totalDiscount, $rowVal);
					$price = bcsub($price, $rowVal);
					break;
			}
		}

		$totalPaymentMarkup = '0';
		if ($this->paymentMarkUp) {
			$totalPaymentMarkup = bcmul($this->paymentMarkUp / 100, $price);
			$price = bcadd($price, $totalPaymentMarkup);
		}

		$adjustment = bcadd($totalDiscount, $totalPaymentMarkup);
		$adjustmentCoefficient = (empty($basicPrice) || empty($itemsTotal['price']))
			? '0'
			: bcdiv($adjustment, $itemsTotal['price'], 6)
		;
		$priceBaseCoefficient = bcsub('1', $adjustmentCoefficient, 6);

		$itemsWithTaxBases = array_map(function ($item) use($priceBaseCoefficient) {
			$item['taxBase'] = bcmul($item['price'], $priceBaseCoefficient, 6);
			return $item;
		}, $this->itemsList);

		$tax = $this->calcTaxes($itemsWithTaxBases);

		if ($tax['mode'] === self::MODE_ADD_TO_TOTAL) {
			$price = round(bcadd($price, $tax['totalTaxAmount'], 6), 2);
		}

		return [
			'itemsSubTotal' => $itemsTotal,
			'price' => $price,
			'discount' => $totalDiscount,
			'paymentMarkup' => $totalPaymentMarkup,
			'tax' => $tax,
			'taxSettings' => $this->taxSettings,
			'servicesSubTotal' => [
				'price' => round(bcadd($this->servicesTotal['price'], $this->shipping['price'], 6), 2),
				'qty' => $this->servicesTotal['qty'] + $this->shipping['qty']
			],
			'calcByAPI' => true
		];
	}

	protected function calcTaxes(array $itemsWithTaxBases): array
	{
		if (!$this->taxSettings['turnedOn']) {
			return ['totalTaxAmount' => null, 'itemsWithTax' => $itemsWithTaxBases, 'mode' => null];
		}

		if ($this->taxSettings['pricesEnteredWithTax']) {
			return $this->calcInclusiveTaxes($itemsWithTaxBases);
		} else {
			return $this->calcExclusiveTaxes($itemsWithTaxBases);
		}
	}

	protected function calcInclusiveTaxes(array $itemsWithTaxBases): array
	{
		$totalTaxAmount = '0';
		/** @var TaxRate[] $shippingTaxRates */
		$shippingTaxRates = [];

		foreach ($itemsWithTaxBases as $i => $item) {
			if ($item['taxStatus'] !== ProductProp::TAX_STATUS_TAXABLE) {
				continue;
			}

			$taxRates = $this->filterTaxRatesBySource($this->findTaxRates($item['taxClassId']), self::CALCULATE_TAX_BASED_ON_STORE_LOCATION);
			$calculatedItemTaxes = $this->calcItemTaxes($taxRates, false, $item, $shippingTaxRates);

			$itemsWithTaxBases[$i]['itemTaxes'] = $calculatedItemTaxes['itemTaxes'];
			$itemsWithTaxBases[$i]['appliedTaxes'] = $calculatedItemTaxes['appliedTaxes'];

			$totalTaxAmount = round(bcadd($totalTaxAmount, $calculatedItemTaxes['totalTaxesByItem'], 6), 2);
		}

		$calculatedShippingTaxes = $this->calcShippingTaxes($shippingTaxRates, false);

		$totalTaxAmount = round(bcadd($totalTaxAmount, $calculatedShippingTaxes['shippingTaxes'], 6), 2);

		return [
			'totalTaxAmount' => $totalTaxAmount,
			'itemsWithTax' => $itemsWithTaxBases,
			'mode' => self::MODE_ALREADY_IN_TOTAL,
			'shipping' => [
				'shippingTaxes' => $calculatedShippingTaxes['shippingTaxes'],
				'appliedTaxes' => $calculatedShippingTaxes['shippingAppliedTaxes']
			]
		];
	}

	protected function calcExclusiveTaxes(array $itemsWithTaxBases): array
	{
		$totalTaxAmount = '0';
		/** @var TaxRate[] $shippingTaxRates */
		$shippingTaxRates = [];

		foreach ($itemsWithTaxBases as $i => $item) {
			if ($item['taxStatus'] !== ProductProp::TAX_STATUS_TAXABLE) {
				continue;
			}

			$taxRates = $this->filterTaxRatesBySource($this->findTaxRates($item['taxClassId']), $this->taxSettings['calculateTaxBasedOn']);
			$calculatedItemTaxes = $this->calcItemTaxes($taxRates, true, $item, $shippingTaxRates);

			$itemsWithTaxBases[$i]['itemTaxes'] = $calculatedItemTaxes['itemTaxes'];
			$itemsWithTaxBases[$i]['appliedTaxes'] = $calculatedItemTaxes['appliedTaxes'];

			$totalTaxAmount = round(bcadd($totalTaxAmount, $calculatedItemTaxes['totalTaxesByItem'], 6), 2);
		}

		$calculatedShippingTaxes = $this->calcShippingTaxes($shippingTaxRates, true);

		$totalTaxAmount = round(bcadd($totalTaxAmount, $calculatedShippingTaxes['shippingTaxes'], 6), 2);

		return [
			'totalTaxAmount' => $totalTaxAmount,
			'itemsWithTax' => $itemsWithTaxBases,
			'mode' => self::MODE_ADD_TO_TOTAL,
			'shipping' => [
				'shippingTaxes' => $calculatedShippingTaxes['shippingTaxes'],
				'appliedTaxes' => $calculatedShippingTaxes['shippingAppliedTaxes']
			]
		];
	}

	/**
	 * @param TaxRate[] $taxRates
	 * @return void
	 */
	protected function calcItemTaxes(array $taxRates, bool $allowCompound, array $item, array &$shippingTaxRates)
	{
		$itemTaxes = '0';
		$itemPriceWithTaxes = $item['taxBase'];
		$appliedTaxes = [];
		foreach ($taxRates as $taxRate) {
			$base = ($allowCompound && $taxRate->is_compound) ? $itemPriceWithTaxes : $item['taxBase'];
			$taxValue = bcdiv(bcmul($base, $taxRate->rate, 6), 100, 6);

			$appliedTaxes[] = [
				'tax_rate_id' => $taxRate->tax_rate_id,
				'base' => $base,
				'rate' => $taxRate->rate,
				'taxValue' => $taxValue
			];

			$itemTaxes = bcadd($itemTaxes, $taxValue, 6);
			$itemPriceWithTaxes = bcadd($itemPriceWithTaxes, $taxValue, 6);

			if ($taxRate->include_shipping) {
				$alreadyAdded = current(array_filter($shippingTaxRates, fn (TaxRate $row) => $row->tax_rate_id === $taxRate->tax_rate_id));
				if (!$alreadyAdded) {
					$shippingTaxRates[] = $taxRate;
				}
			}
		}

		//round to 2 symbols
		$itemTaxes = strval(round($itemTaxes, 2));
		$totalTaxesByItem = round(bcmul($itemTaxes, $item['qty'], 6), 2);

		return [
			'itemTaxes' => $itemTaxes,
			'totalTaxesByItem' => $totalTaxesByItem,
			'appliedTaxes' => $appliedTaxes
		];
	}

	/**
	 * @param TaxRate[] $shippingTaxRates
	 * @return void
	 */
	protected function calcShippingTaxes(array $shippingTaxRates, bool $allowCompound): array
	{
		$shippingTaxes = '0';
		$shippingPriceWithTaxes = $this->shipping['price'];
		$shippingAppliedTaxes = [];
		foreach ($shippingTaxRates as $taxRate) {
			$base = ($allowCompound && $taxRate->is_compound) ? $shippingPriceWithTaxes : $this->shipping['price'];
			$taxValue = bcdiv(bcmul($base, $taxRate->rate, 6), 100, 6);

			$shippingAppliedTaxes[] = [
				'tax_rate_id' => $taxRate->tax_rate_id,
				'base' => $base,
				'rate' => $taxRate->rate,
				'taxValue' => $taxValue
			];

			$shippingTaxes = round(bcadd($shippingTaxes, $taxValue, 6), 2);
			$shippingPriceWithTaxes = round(bcadd($shippingPriceWithTaxes, $taxValue, 6), 2);
		}

		return ['shippingTaxes' => strval($shippingTaxes), 'shippingAppliedTaxes' => $shippingAppliedTaxes];
	}

	/**
	 * @param int|null $classId
	 * @return TaxRate[]
	 */
	public function findTaxRates(int|null $classId = null): array
	{
		/** @var ?TaxClass $taxClass */
		$taxClass = current(array_filter($this->taxClasses, function(TaxClass $taxClass) use($classId) {
			if ($classId) {
				return $taxClass->tax_class_id == $classId;
			} else {
				return $taxClass->is_default;
			}
		}));

		return isset($taxClass, $taxClass->taxRates) ? $taxClass->taxRates : [];
	}

	/**
	 * @param TaxRate[] $taxRates
	 * @param string $calculateTaxBasedOn
	 * @return TaxRate[]
	 */
	public function filterTaxRatesBySource(array $taxRates, string $calculateTaxBasedOn): array
	{
		if ($calculateTaxBasedOn === self::CALCULATE_TAX_BASED_ON_STORE_LOCATION) {
			return array_filter($taxRates, fn (TaxRate $row) => $row->country_id === null);
		} else if ($calculateTaxBasedOn === self::CALCULATE_TAX_BASED_ON_CUSTOMER_SHIPPING_ADDRESS) {
			return array_filter(
				$taxRates,
				fn (TaxRate $row) => $this->shippingLocation?->country_id && ($this->shippingLocation?->country_id == $row->country_id || $row->country_id === null)
			);
		} else if ($calculateTaxBasedOn === self::CALCULATE_TAX_BASED_ON_CUSTOMER_BILLING_ADDRESS) {
			return array_filter(
				$taxRates,
				fn (TaxRate $row) => $this->billingLocation?->country_id && ($this->billingLocation?->country_id == $row->country_id || $row->country_id === null)
			);
		} else {
			throw new \RuntimeException('Unknown taxSetting for filterTaxRatesBySource"' . $calculateTaxBasedOn . '"');
		}

		return [];
	}

	public function setTaxSettings(array $taxSettings): self
	{
		$this->taxSettings = $taxSettings;
		return $this;
	}

	/**
	 * @param TaxClass[] $taxClasses
	 * @return $this
	 */
	public function setTaxClasses(array $taxClasses): self
	{
		$this->taxClasses = $taxClasses;
		return $this;
	}

	public function setShippingLocation(PersonAddress|null $value): self
	{
		$this->shippingLocation = $value;
		return $this;
	}

	public function setBillingLocation(PersonAddress|null $value): self
	{
		$this->billingLocation = $value;
		return $this;
	}

	public function calcTotalForItems(): array
	{
		$price = '0';
		$qty = 0;
		foreach ($this->itemsList as $item) {
			$price = round(bcadd($price, bcmul($item['price'], $item['qty'], 6), 6), 2);
			$qty += $item['qty'];
		}

		return ['price' => $price, 'qty' => $qty];
	}
}
