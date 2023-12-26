<?php

namespace app\modules\files\formModels;

use app\modules\catalog\models\Category;
use app\modules\catalog\models\Manufacturer;
use app\modules\catalog\models\Product;
use app\modules\catalog\models\ProductImage;
use app\modules\cms\models\Image;
use app\modules\files\validators\FileNameValidator;
use app\modules\system\models\Lang;
use app\modules\system\models\Site;
use Yii;
use yii\db\Expression;
use yii\imagine\Image as ImagineImg;

class ImageChunkUploaderForm extends FileChunkUploaderForm
{
	public $for_product_id;
	public $for_category_id;
	public $for_manufacturer_id;

	protected ?Image $image;

	public function rules(): array
	{
		return array_merge(
			$this->getBasicRules(),
			[
				['file_name', FileNameValidator::class, 'extensions' => ['jpg', 'jpeg', 'png', 'gif']],
				[
					'for_product_id',
					'exist',
					'targetClass' => Product::class,
					'targetAttribute' => 'product_id'
				],
				[
					'for_category_id',
					'exist',
					'targetClass' => Category::class,
					'targetAttribute' => 'category_id'
				],
				[
					'for_manufacturer_id',
					'exist',
					'targetClass' => Manufacturer::class,
					'targetAttribute' => 'manufacturer_id'
				],
				['for_product_id', 'validateEssenceSpecified', 'skipOnEmpty' => false]
			]
		);
	}

	public function save(): bool
	{
		if (!$this->validate()) {
			return false;
		}

		$this->setupInitialRow();
		$this->uploadChunkToS3();
		$this->saveFileFromChunks();

		return true;
	}

	protected function onFileUploaded(string $localPath)
	{
		$saveAs = tempnam(sys_get_temp_dir(), 'img_uploader');
		$this->getInstanceBucketTools()->downloadFile($localPath, $saveAs);

		$img = ImagineImg::getImagine()->open($saveAs);
		$imgSize = $img->getSize();

		$this->image = new Image();
		$this->image->attributes = [
			'site_id' => Site::DEFAULT_SITE,
			'lang_id' => Lang::DEFAULT_LANG,
			'name' => $this->initialRow['data']['fileName'] ?? null,
			'size' => filesize($saveAs),
			'path' => $localPath,
			'width' => $imgSize?->getWidth(),
			'height' => $imgSize?->getHeight(),
			'used_in' => $this->getUsedIn(),
			'mime_type' => $this->initialRow['data']['mimeType'] ?? null,
		];
		$this->image->save(false);

		$this->bindToEssence();
		$this->image->refresh();
	}

	protected function bindToEssence()
	{
		if (isset($this->for_product_id)) {
			$productImage = new ProductImage();
			$productImage->attributes = [
				'product_id' => $this->for_product_id,
				'image_id' => $this->image->image_id,
				'is_default' => false
			];
			$productImage->save(false);
		} else if (isset($this->for_category_id)) {
			Category::updateAll(['image_id' => $this->image->image_id], ['category_id' => $this->for_category_id]);
		} else if (isset($this->for_manufacturer_id)) {
			Manufacturer::updateAll(['image_id' => $this->image->image_id], ['manufacturer_id' => $this->for_manufacturer_id]);
		}
	}

	protected function getUsedIn(): null|Expression
	{
		$usedIn = null;
		if (isset($this->for_product_id)) {
			$usedIn = new Expression("'{product}'");
		} else if (isset($this->for_category_id)) {
			$usedIn = new Expression("'{category}'");
		} else if (isset($this->for_manufacturer_id)) {
			$usedIn = new Expression("'{manufacturer}'");
		}

		return $usedIn;
	}
	public function validateEssenceSpecified()
	{
		if (empty($this->for_product_id) && empty($this->for_category_id) && empty($this->for_manufacturer_id)) {
			$this->addError('for_product_id', Yii::t('app', 'To upload image you need to specify an essence, specify any of the attribute: for_product_id, for_category_id, for_manufacturer_id.'));
			return;
		}
	}

	public function getImage(): ?Image
	{
		return $this->image;
	}
}
