<?php

namespace app\modules\catalog\models;

use app\helpers\Util;
use app\modules\cms\models\Image;
use app\modules\cms\models\MenuBlock;
use app\modules\system\models\Setting;
use Cocur\Slugify\Slugify;
use Yii;
use app\modules\system\models\Lang;
use app\modules\system\models\Site;
use app\modules\catalog\activeQueries\CategoryQuery;
use yii\helpers\StringHelper;

/**
 * This is the model class for table "category".
 *
 * @property int $category_id
 * @property int|null $parent_id
 * @property int $site_id
 * @property int $sort
 * @property string $created_at
 * @property string|null $deleted_at
 * @property string|null $external_id
 * @property string $status
 * @property int|null $created_by
 *
 * @property MenuBlock[] $blocks
 * @property Category[] $categories
 * @property CategoryMenuRel[] $categoryMenuRels
 * @property CategoryProp $categoryProp
 * @property CategoryText[] $categoryTexts
 * @property CategoryText $categoryTextDefault
 * @property Person $createdBy
 * @property Lang[] $langs
 * @property MenuItemRel[] $menuItemRels
 * @property OneCGroup[] $oneCGroups
 * @property Category $parent
 * @property ProductCategoryRel[] $productCategoryRels
 * @property ProductImportRel[] $productImportRels
 * @property Product[] $products
 * @property Site $site
 * @property Image $image
 */
class Category extends \yii\db\ActiveRecord
{
	const STATUS_PUBLISHED = 'published';
	const STATUS_HIDDEN = 'hidden';

	public $products_qty;

	public $children;

	public $siblings;

	public $parentsCategories;

	public $filter;

	public $compiledSeoProps;

	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'category';
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
			[['parent_id', 'site_id', 'sort', 'created_by'], 'default', 'value' => null],
			[['parent_id', 'site_id', 'sort', 'created_by'], 'integer'],
			[['site_id', 'sort'], 'required'],
			[['created_at', 'deleted_at', 'image_id'], 'safe'],
			[['external_id', 'status'], 'string'],
			[['external_id'], 'unique'],
			[['status', 'created_by'], 'unique', 'targetAttribute' => ['status', 'created_by']],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'category_id' => 'Category ID',
			'parent_id' => 'Parent ID',
			'site_id' => 'Site ID',
			'sort' => 'Sort',
			'created_at' => 'Created At',
			'deleted_at' => 'Deleted At',
			'external_id' => 'External ID',
			'status' => 'Status',
			'created_by' => 'Created By',
		];
	}

	/**
	 * Gets query for [[Blocks]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getBlocks()
	{
		return $this->hasMany(MenuBlock::className(), ['block_id' => 'block_id'])->viaTable('category_menu_rel', ['category_id' => 'category_id']);
	}

	/**
	 * Gets query for [[Categories]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getCategories()
	{
		return $this->hasMany(Category::class, ['parent_id' => 'category_id']);
	}

	/**
	 * Gets query for [[CategoryMenuRels]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getCategoryMenuRels()
	{
		return $this->hasMany(CategoryMenuRel::className(), ['category_id' => 'category_id']);
	}

	/**
	 * Gets query for [[CategoryProp]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getCategoryProp()
	{
		return $this->hasOne(CategoryProp::class, ['category_id' => 'category_id']);
	}

	/**
	 * Gets query for [[Image]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getImage()
	{
		return $this->hasOne(Image::class, ['image_id' => 'image_id']);
	}

	/**
	 * Gets query for [[CategoryTexts]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getCategoryTexts()
	{
		return $this->hasMany(CategoryText::class, ['category_id' => 'category_id']);
	}

	public function getCategoryTextDefault()
	{
		return $this->hasOne(CategoryText::class, ['category_id' => 'category_id'])
			->andWhere(['category_text.lang_id' => Lang::DEFAULT_LANG])
		;
	}

	/**
	 * Gets query for [[CreatedBy]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getCreatedBy()
	{
		return $this->hasOne(Person::className(), ['person_id' => 'created_by']);
	}

	/**
	 * Gets query for [[Langs]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getLangs()
	{
		return $this->hasMany(Lang::class, ['lang_id' => 'lang_id'])->viaTable('category_text', ['category_id' => 'category_id']);
	}

	/**
	 * Gets query for [[MenuItemRels]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getMenuItemRels()
	{
		return $this->hasMany(MenuItemRel::className(), ['category_id' => 'category_id']);
	}

	/**
	 * Gets query for [[OneCGroups]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getOneCGroups()
	{
		return $this->hasMany(OneCGroup::className(), ['category_id' => 'category_id']);
	}

	/**
	 * Gets query for [[Parent]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getParent()
	{
		return $this->hasOne(Category::class, ['category_id' => 'parent_id']);
	}

	/**
	 * Gets query for [[ProductCategoryRels]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getProductCategoryRels()
	{
		return $this->hasMany(ProductCategoryRel::class, ['category_id' => 'category_id']);
	}

	/**
	 * Gets query for [[ProductImportRels]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getProductImportRels()
	{
		return $this->hasMany(ProductImportRel::className(), ['category_id' => 'category_id']);
	}

	/**
	 * Gets query for [[Products]].
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getProducts()
	{
		return $this->hasMany(Product::class, ['product_id' => 'product_id'])->viaTable('product_category_rel', ['category_id' => 'category_id']);
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

	public function setChildren($value): self
	{
		$this->children = $value;
		return $this;
	}

	public function setSiblings($value): self
	{
		$this->siblings = $value;
		return $this;
	}

	public function setParentsCategories($value): self
	{
		$this->parentsCategories = $value;
		return $this;
	}

	public function fetchFilter(): Filter|null
	{
		$this->filter = null;
		if ($this->categoryProp->use_filter) {
			$query = Filter::find()->withFields();

			if ($this->categoryProp->filter_id) {
				$query->where(['filter_id' => $this->categoryProp->filter_id]);
			} else {
				$query->where('is_default is true');
			}

			$this->filter = $query->one();
		}

		return $this->filter;
	}

	public function makeCompiledSeoProps(): self
	{
		$seoTemplates = Setting::getSeoTemplates();
		$variables = $this->getVariablesForSeoTpls();

		$seo = [
			'compiledTitle' => null,
			'compiledMetaDescription' => null,
			'customTitle' => $this->categoryTexts[0]->custom_title,
			'customMetaDesc' => $this->categoryTexts[0]->meta_description,
		];

		$m = new \Mustache_Engine(['entity_flags' => ENT_QUOTES]);
		if (isset($seoTemplates['category'])) {
			$seo['compiledTitle'] = $m->render($seoTemplates['category']['title'], $variables);
			$seo['compiledMetaDescription'] = $m->render($seoTemplates['category']['metaDescription'], $variables);
		}

		$seo['title'] = $seo['customTitle'] ?: $seo['compiledTitle'];
		$seo['metaDesc'] = $seo['customMetaDesc'] ?: $seo['compiledMetaDescription'];

		$this->compiledSeoProps = $seo;

		return $this;
	}

	public function setCompiledSeoProps($props): self
	{
		$this->compiledSeoProps = $props;
		return $this;
	}

	public function getVariablesForSeoTpls(): array
	{
		return [
			'id' => $this->category_id,
			'parentId' => $this->parent_id,
			'title' => $this->categoryTexts[0]->title,
			'topDescription' => $this->categoryTexts[0]->getDescriptionTopAsText(),
			'bottomDescription' => $this->categoryTexts[0]->getDescriptionBottomAsText(),
			'shortTopDescription' => StringHelper::truncate($this->categoryTexts[0]->getDescriptionTopAsText(), 100),
			'shortBottomDescription' => StringHelper::truncate($this->categoryTexts[0]->getDescriptionBottomAsText(), 100),
		];
	}

	public static function find(): CategoryQuery
	{
		return new CategoryQuery(get_called_class());
	}

	public static function findUniqueUrlKeyByTitle(string $title, int|null $categoryId = null): string
	{
		$slugify = new Slugify();
		$basicSlug = $slugify->slugify(mb_strtolower($title), '-');

		$i = 0;
		$rndLen = 5;
		while (true) {
			$slug = $basicSlug;
			if ($i > 0) {
				$rnd = Util::getRndStr($rndLen, 'letnum', false);
				$slug .= '-' . $rnd;
			}

			$query = CategoryText::find()
				->where(['url_key' => $slug])
			;

			if ($categoryId) {
				$query->andWhere('category_id != :categoryId', ['categoryId' => $categoryId]);
			}

			$total = $query->count();
			if ($total == 0) {
				return $slug;
			} elseif ($i > 4) {
				$rndLen++;
			}

			$i++;
		}
	}

	public function showInCategoryMenu()
	{
		/** @var MenuBlock $menuBlock */
		$menuBlock = MenuBlock::find()
			->where(['site_id' => Site::DEFAULT_SITE, 'key' => MenuBlock::KEY_CATEGORY])
			->one();
		;

		if (!$menuBlock) {
			throw new \RuntimeException('Category Menu block isnt found');
		}

		self::getDb()->createCommand("
			insert into category_menu_rel
				(category_id, block_id)
			select
				category_id,
				:block
			from
				category
			where
				category.category_id in (:category)
			on conflict do nothing
		")
			->bindValues([
				'block' => $menuBlock->block_id,
				'category' => $this->category_id
			])
			->execute()
		;
	}

	public function hideFromCategoryMenu()
	{
		self::getDb()->createCommand("
			delete from category_menu_rel
			using menu_block
			where
				category_menu_rel.block_id = menu_block.block_id
				and menu_block.key = :categoryMenuBlock
				and category_menu_rel.category_id in (:category)
				and menu_block.site_id = :site
		")
			->bindValues([
				'categoryMenuBlock' => MenuBlock::KEY_CATEGORY,
				'category' => $this->category_id,
				'site' => Site::DEFAULT_SITE
			])
			->execute()
		;
	}

	/**
	 * Since product_category_rel contains also relations for parent categories,
	 * we need to rm or add products from/to old and new parent categories.
	 *
	 * @param int|null $newParentId
	 * @return void
	 */
	public function changeParent(int|null $newParentId): void
	{
		if ($this->parent_id) {
			ProductCategoryRel::removeCategoryProductsFromParents($this->category_id, $this->parent_id);
		}

		//change parent and sort - add category to the end:
		self::updateAll([
			'parent_id' => $newParentId,
			'sort' => null
		], ['category_id' => $this->category_id]);

		if ($newParentId) {
			ProductCategoryRel::addCategoryProductsToParents($this->category_id, $newParentId);
		}
	}

	public function findAllParentIds(): array
	{
		$out = [];
		$rows = self::getDb()->createCommand("select category_id from category_get_parents(:id)")
			->bindValues(['id' => $this->category_id])
			->queryAll()
		;

		foreach ($rows as $row) {
			$out[] = $row['category_id'];
		}

		return $out;
	}

	public function findAllChildrenIds(): array
	{
		$out = [];
		$rows = self::getDb()->createCommand("select category_id from category_get_children(:id)")
			->bindValues(['id' => $this->category_id])
			->queryAll()
		;

		foreach ($rows as $row) {
			$out[] = $row['category_id'];
		}

		return $out;
	}

	public function fields()
	{
		$out = ['category_id'];
		$out['title'] = fn () => $this->categoryTexts[0]->title;
		$out['url_key'] = fn () => $this->categoryTexts[0]->url_key;
		$out[] = 'parent_id';
		$out[] = 'external_id';

		if (isset($this->products_qty)) {
			$out['products_qty'] = function (self $model) {
				return $model->products_qty;
			};
		}

		$out['text'] = function () {
			return $this->categoryTexts[0];
		};

		if ($this->isRelationPopulated('categoryProp') && $this->categoryProp) {
			$out['props'] = function () {
				return $this->categoryProp;
			};
		}

		if ($this->isRelationPopulated('image') && $this->image) {
			$out['image'] = function () {
				return $this->image;
			};
		}

		if (isset($this->children)) {
			$out['children'] = function (self $model) {
				return $model->children;
			};
		}

		if (isset($this->siblings)) {
			$out['siblings'] = function (self $model) {
				return $model->siblings;
			};
		}

		if (isset($this->parentsCategories)) {
			$out['parents'] = function (self $model) {
				return $model->parentsCategories;
			};
		}

		if (isset($this->filter)) {
			$out['filter'] = function (self $model) {
				return $model->filter;
			};
		}

		if (isset($this->compiledSeoProps)) {
			$out['seo'] = function (self $model) {
				return $model->compiledSeoProps;
			};
		}

		$out[] = 'status';
		$out[] = 'created_by';
		$out[] = 'created_at';
		$out[] = 'deleted_at';
		$out[] = 'sort';

		return $out;
	}
}
