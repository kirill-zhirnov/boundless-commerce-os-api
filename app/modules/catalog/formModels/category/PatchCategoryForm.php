<?php

namespace app\modules\catalog\formModels\category;

use Yii;
use app\components\InstancesQueue;
use app\modules\catalog\models\Category;

class PatchCategoryForm extends CategoryForm
{
	public function rules(): array
	{
		return $this->getBasicRules();
	}

	public function save(): bool
	{
		if (!isset($this->category)) {
			throw new \RuntimeException('For Patch Model a category should be set prior calling this func');
		}

		if (!$this->validate()) {
			return false;
		}

		$this->saveCategoryFields();
		$this->saveTextFields();
		$this->saveProps();
		$this->saveShowInCategoryMenu();

		$this->refetchCategory();
		/** @var InstancesQueue $queue */
		$queue = Yii::$app->queue;
		$queue->modelUpdated(Category::class, [$this->category->category_id]);

		return true;
	}

	protected function saveProps()
	{
		$shallSave = false;
		$categoryProp = $this->category->categoryProp;

		foreach (['use_filter', 'custom_link', 'arbitrary_data'] as $field) {
			if (!is_null($this->{$field})) {
				$shallSave = true;

				switch ($field) {
					case 'use_filter':
						$categoryProp->use_filter = $this->use_filter;
						break;
					case 'custom_link':
						$categoryProp->custom_link = $this->custom_link;
						break;
					case 'arbitrary_data':
						$categoryProp->arbitrary_data = $this->arbitrary_data;
						break;
				}
			}
		}

		if ($shallSave) {
			$categoryProp->save(false);
		}
	}

	protected function saveTextFields()
	{
		$shallSave = false;
		$categoryText = $this->category->categoryTextDefault;

		foreach (['title', 'url_key', 'description_top', 'description_bottom'] as $field) {
			if (!is_null($this->{$field})) {
				$shallSave = true;

				switch ($field) {
					case 'title':
						$categoryText->title = trim($this->title);
						break;
					case 'url_key':
						$categoryText->url_key = $this->url_key;
						break;
					case 'description_top':
						$categoryText->description_top = $this->description_top;
						break;
					case 'description_bottom':
						$categoryText->description_bottom = $this->description_bottom;
						break;
				}
			}
		}

		if ($shallSave) {
			$categoryText->save(false);
		}
	}

	protected function saveCategoryFields()
	{
		$shallSave = false;
		foreach (['sort', 'external_id', 'publishing_status'] as $field) {
			if (!is_null($this->{$field})) {
				$shallSave = true;

				switch ($field) {
					case 'sort':
						$this->category->sort = $this->sort;
						break;
					case 'external_id':
						$this->category->external_id = $this->external_id;
						break;
					case 'publishing_status':
						$this->category->status = $this->publishing_status;
						break;
				}
			}
		}

		if (!empty($shallSave)) {
			$this->category->save(false);
		}

		if (!is_null($this->parent_id)) {
			$oldParentId = is_null($this->category->parent_id) ? -1 : $this->category->parent_id;

			if ($this->parent_id != $oldParentId) {
				$this->category->changeParent(
					$this->parent_id === -1 ? null : $this->parent_id
				);
			}
		}
	}
}
