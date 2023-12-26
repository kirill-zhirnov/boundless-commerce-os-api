<?php

namespace app\modules\catalog\traits;

use app\modules\catalog\components\ProductLoader;
use app\modules\catalog\models\Product;
use yii\web\NotFoundHttpException;

trait ProductHelpers
{
	public function findProductWithLoader(int|string $productId, bool|null $onlyPublished = null): Product
	{
		$productLoader = new ProductLoader($productId);

		if (isset($onlyPublished)) {
			$productLoader->setPublishedStatus($onlyPublished ? null : 'all');
		}

		return $productLoader->load();
	}

	public function findProductById(int|string $id): Product
	{
		$id = intval($id);

		/** @var Product $product */
		$product = Product::find()
			->where(['product_id' => $id])
			->one()
		;

		if (!$product) {
			throw new NotFoundHttpException('Product not found');
		}

		return $product;
	}

	/**
	 * Function needs to transform outdated attribute `props` => `attrs` and so on.
	 *
	 * @param array $queryParams
	 * @return array
	 */
	public function transformLegacyQuery(array $queryParams)
	{
		if (isset($queryParams['props']) && !isset($queryParams['attrs'])) {
			$queryParams['attrs'] = $queryParams['props'];
			unset($queryParams['props']);
		}

		return $queryParams;
	}
}
