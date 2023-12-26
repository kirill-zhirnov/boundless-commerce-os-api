<?php

namespace app\modules\catalog\formModels\category;

use app\components\InstancesQueue;
use app\modules\catalog\models\Category;
use app\modules\catalog\models\CategoryText;
use app\modules\catalog\validators\CategoryExistsValidator;
use app\modules\system\models\Lang;
use app\modules\system\models\Site;
use app\validators\NullOnEmptyStringFilter;
use app\validators\UrlKeyValidator;
use Yii;
use yii\base\Model;
use yii\db\Query;

class CategoryForm extends Model
{
	public $title;
	public $url_key;
	public $description_top;
	public $description_bottom;
	public $publishing_status;
	public $parent_id;
	public $external_id;
	public $sort;
	public $use_filter;
	public $custom_link;
//	public $show_in_parent_page_menu;
	public $show_in_category_menu;
	public $arbitrary_data;

	protected ?Category $category;

	public function rules(): array
	{
		return array_merge(
			$this->getBasicRules(),
			[
				[['title'], 'required'],
				[['title'], 'trim'],
			]
		);
	}

	public function save(): bool
	{
		if (!$this->validate()) {
			return false;
		}

		if (!isset($this->category)) {
			$this->category = new Category();
		}

		$isNew = $this->category->isNewRecord;
		$this->category->attributes = [
			'parent_id' => $this->parent_id === -1 ? null : $this->parent_id,
			'site_id' => Site::DEFAULT_SITE,
			'status' => $this->publishing_status ?? Category::STATUS_PUBLISHED,
			'external_id' => $this->external_id,
			'sort' => $this->sort
		];
		$this->category->save(false);

		$categoryText = $this->category->categoryTextDefault;
		$categoryText->attributes = [
			'title' => $this->title,
			'url_key' => $this->url_key ?? Category::findUniqueUrlKeyByTitle($this->title, $this->category->category_id),
			'description_top' => $this->description_top,
			'description_bottom' => $this->description_bottom,
		];
		$categoryText->save(false);

		$categoryProp = $this->category->categoryProp;
		$categoryProp->attributes = [
			'use_filter' => $this->use_filter ?? true,
			'custom_link' => $this->custom_link,
			'arbitrary_data' => $this->arbitrary_data
//			'show_in_parent_page_menu' => $this->show_in_parent_page_menu ?? true
		];
		$categoryProp->save(false);

		$this->saveShowInCategoryMenu();

		$this->refetchCategory();

		/** @var InstancesQueue $queue */
		$queue = Yii::$app->queue;
		if ($isNew) {
			$queue->modelCreated(Category::class, [$this->category->category_id]);
		} else {
			$queue->modelUpdated(Category::class, [$this->category->category_id]);
		}

		return true;
	}

	public function setCategory(?Category $category): CategoryForm
	{
		$this->category = $category;

		return $this;
	}

	public function getCategory(): ?Category
	{
		return $this->category;
	}

	public function validateArbitraryData()
	{
		if (isset($this->arbitrary_data) && !is_array($this->arbitrary_data)) {
			$this->addError('arbitrary_data', Yii::t('app', 'Arbitrary data should be a key-value object.'));
			return;
		}
	}

	public function getBasicRules(): array
	{
		return [
			[['title', 'url_key'], 'string', 'max' => 1000],
			[['url_key'], UrlKeyValidator::class],
			[
				['url_key'],
				'unique',
				'targetClass' => CategoryText::class,
				'targetAttribute' => 'url_key',
				'filter' => function(Query $query) {
					$query->andWhere(['lang_id' => Lang::DEFAULT_LANG]);

					if (isset($this->category) && !$this->category->isNewRecord) {
						$query->andWhere('category_id != :categoryId', ['categoryId' => $this->category->category_id]);
					}
				}
			],
			[['description_top', 'description_bottom'], 'string', 'max' => 66000],
			[
				'publishing_status',
				'in',
				'range' => [Category::STATUS_PUBLISHED, Category::STATUS_HIDDEN]
			],
			[
				'parent_id',
				CategoryExistsValidator::class,
//				'exist',
//				'targetClass' => Category::class,
//				'targetAttribute' => 'category_id',
				'filter' => function(Query $query) {
					if (isset($this->category) && !$this->category->isNewRecord) {
						$allChildren = $this->category->findAllChildrenIds();
						$notIn = array_merge([$this->category->category_id], $allChildren);

						//parent can't be itself - prevent infinite loop
						$query->andWhere('category.category_id not in (' . implode(',', $notIn) . ')');
					}
				}
			],
			['external_id', 'string', 'max' => 1000],
			[
				'external_id',
				'unique',
				'targetClass' => Category::class,
				'targetAttribute' => 'external_id',
				'filter' => function(Query $query) {
					if (isset($this->category) && !$this->category->isNewRecord) {
						$query->andWhere('category_id != :categoryId', ['categoryId' => $this->category->category_id]);
					}
				}
			],
			['sort', 'integer'],
//			[['show_in_parent_page_menu'], 'boolean'],
			[['show_in_category_menu'], 'boolean'],
			[['use_filter'], 'boolean'],
			[['custom_link'], 'string', 'max' => 500],
			[
				['url_key', 'external_id', 'custom_link'],
				NullOnEmptyStringFilter::class, 'skipOnEmpty' => false
			],
			['arbitrary_data', 'validateArbitraryData']
		];
	}

	protected function refetchCategory()
	{
		$this->category = Category::find()
			->withPublicScope()
			->where(['category.category_id' => $this->category->category_id])
			->one()
		;
		$this->category->makeCompiledSeoProps();
	}

	protected function saveShowInCategoryMenu()
	{
		if (isset($this->show_in_category_menu)) {
			if ($this->show_in_category_menu) {
				$this->category->showInCategoryMenu();
			} else {
				$this->category->hideFromCategoryMenu();
			}
		}
	}
}
