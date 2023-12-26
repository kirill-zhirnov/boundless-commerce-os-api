<?php

namespace app\modules\catalog\models;

use app\modules\music\models\MusicalAlbumMaecenateRel;
use Yii;

/**
 * This is the model class for table "product_category_rel".
 *
 * @property int $category_id
 * @property int $product_id
 * @property bool $is_default
 * @property int|null $sort
 *
 * @property Category $category
 * @property Product $product
 */
class ProductCategoryRel extends \yii\db\ActiveRecord
{
	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'product_category_rel';
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
			[['category_id', 'product_id'], 'required'],
			[['category_id', 'product_id', 'sort'], 'default', 'value' => null],
			[['category_id', 'product_id', 'sort'], 'integer'],
			[['is_default'], 'boolean'],
			[['product_id', 'is_default'], 'unique', 'targetAttribute' => ['product_id', 'is_default']],
			[['category_id', 'product_id'], 'unique', 'targetAttribute' => ['category_id', 'product_id']],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'category_id' => 'Category ID',
			'product_id' => 'Product ID',
			'is_default' => 'Is Default',
			'sort' => 'Sort',
		];
	}

	/**
	 * Gets query for [[Category]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getCategory()
	{
		return $this->hasOne(Category::class, ['category_id' => 'category_id']);
	}

	/**
	 * Gets query for [[Product]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getProduct()
	{
		return $this->hasOne(Product::class, ['product_id' => 'product_id']);
	}

	public static function assignDefaultCategory(int $productId, int $defaultCategoryId)
	{
		ProductCategoryRel::updateAll(
			['is_default' => false],
			'product_id = :product and category_id != :defaultCategory',
			['product' => $productId, 'defaultCategory' => $defaultCategoryId]
		);

		ProductCategoryRel::updateAll(
			['is_default' => true],
			['product_id' => $productId, 'category_id' => $defaultCategoryId],
		);
	}

	public static function setProductCategories(int $productId, array $categories)
	{
		$activeCategories = [];
		foreach ($categories as $categoryId) {
			$activeCategories[] = $categoryId;
			$parentIds = self::addProductToCategory($productId, $categoryId);
			$activeCategories = array_merge($activeCategories, $parentIds);
		}

		$deleteCondition = ['product_id' => $productId];
		if (!empty($activeCategories)) {
			$deleteCondition = [
				'and',
				['product_id' => $productId],
				['not in', 'category_id', $activeCategories]
			];
		}

		ProductCategoryRel::deleteAll($deleteCondition);
	}

	public static function addProductToCategory(int $productId, int $categoryId): array
	{
		$parentCategories = self::getDb()->createCommand("
			select
				category_id
			from
				category_get_parents(:category)
			where
				category_id != :category
		")
			->bindValues(['category' => $categoryId])
			->queryColumn();

		$bindToCategories = array_merge([$categoryId], $parentCategories);
		self::getDb()->createCommand("
			insert into product_category_rel
				(category_id, product_id)
			select
				category_id,
				:product
			from
				category
			where
				category_id in (" . implode(',', $bindToCategories) . ")
			on conflict do nothing
		")
			->bindValues(['product' => $productId])
			->execute()
		;

		return $parentCategories;
	}

	public static function removeCategoryProductsFromParents(int $categoryIdWithProducts, int $removeFromId): void
	{
		self::getDb()->createCommand("
			delete from
				product_category_rel
			where
				category_id = :removeFromId
				and product_id in (
					select product_id from product_category_rel where category_id = :categoryId
				)
				and product_id not in (
					select
						product_id
					from
						product_category_rel
					where
						category_id in (
							select
								category_id
							from
								category
							where
								parent_id = :removeFromId
								and category_id != :categoryId
						)
				)
		")
			->bindValues(['removeFromId' => $removeFromId, 'categoryId' => $categoryIdWithProducts])
			->execute()
		;

		$category = Category::findOne($removeFromId);
		if ($category->parent_id) {
			self::removeCategoryProductsFromParents($categoryIdWithProducts, $category->parent_id);
		}
	}

	public static function addCategoryProductsToParents(int $categoryIdWithProducts, int $addToCategoryId): void
	{
		self::getDb()->createCommand("
			insert into product_category_rel
				(category_id, product_id)
			select
				distinct
				parents.category_id,
				product_category_rel.product_id
			from
				category_get_parents(:addToCategoryId) as parents,
				product_category_rel
			where
				product_category_rel.category_id = :categoryIdWithProducts
			on conflict do nothing
		")
			->bindValues([
				'categoryIdWithProducts' => $categoryIdWithProducts,
				'addToCategoryId' => $addToCategoryId
			])
			->execute()
		;
	}

	public function fields(): array
	{
		$out = ['is_default'];

		if ($this->isRelationPopulated('category')) {
			$out['category'] = function () {
				return $this->category;
			};
		}

		return $out;
	}
}
