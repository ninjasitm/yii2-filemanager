<?php

namespace nitm\filemanager\helpers;

use Yii;
use yii\helpers\Html;
use yii\web\UploadedFile;
use kartik\icons\Icon;
use yii\imagine\Image as BaseImage;
use nitm\filemanager\helpers\Storage;
use nitm\filemanager\models\Image;
use nitm\filemanager\models\ImageMetadata;
use Imagick;

/**
 * This is the model class for table "images".
 *
 * @property integer $id
 * @property integer $category_id
 * @property integer $content_id
 * @property string $url
 * @property string $slug
 * @property string $created
 * @property string $updated
 *
 * @property Content $content
 * @property Categories $category
 * @property ImagesMetadata $metadata
 */
class ImageHelper extends \yii\helpers\FileHelper
{
	/**
	 * @mixed thumbnail sizes to create
	 */
	public static $sizes = [];
	
	/**
	 * @mixed size mapping
	 */
	private static $_sizes = [
		'small' => [
			'sizeX' => 64,
			'sizeY' => 64,
			'quality' => 90
		],
		'medium' => [
			'sizeX' => 256,
			'sizeY' => 256,
			'quality' => 90
		],
		'large' => [
			'sizeX' => 512,
			'sizeY' => 512,
			'quality' => 90
		]
	];
	
	public function getDirectory($getAlias=false)
	{
		$dir = \Yii::$app->getModule('nitm-files')->getPath('images');
		if($getAlias)
			$dir = \Yii::getAlias($dir);
		return $dir;
	}
	
	/**
	 * Save uploaded images
	 * @param Entity $model The model images are being saved for
	 * @param string $name
	 */
	public static function saveImages($model, $name)
	{
		// retrieve all uploaded files for name images
		$ret_val = false;
		$name = self::getSafeName($name);
		$uploads = UploadedFile::getInstances($model, 'images');
		foreach($uploads as $idx=>$uploadedFile) 
		{
			switch(empty($uploadedFile->name) && file_exists($uploadedFile->tempName))
			{
				case false:
				$image = new Image(['scenario' => 'create']);
				$directory = static::getDirectory().$model->isWhat()."/".$name."/";
				$image->is_default = @($_FILES[$model->formName()]['tmp_name']['images']['default'] == $uploadedFile->tempName) ? 1 : 0;
				$image->url = $directory.($image->is_default ?  'default' : 'extra')."-image-".md5($uploadedFile->name).".".$uploadedFile->getExtension();
				$image->hash = Image::getHash($uploadedFile->tempName);
				$model->size = $uploadedFile->size;
				$image->parent_type = $model->isWhat();
				$existing = Image::find()->where([
					"hash" => $image->hash,
					'parent_type' => $model->isWhat()
				])->one();
				$idField = ($this instanceof Category) ? 'category_id' : 'content_id';
				switch($existing instanceof Image)
				{
					//If an image already exists for this file then swap images
					case true:
					$image = $existing;
					\Yii::$app->getSession()->setFlash(
						'warning',
						"Found dangling ".$model->isWhat()." image ".$image->slug
					);
					self::createThumbnails($image);
					$existing->$idField = $model->getId();
					$existing->save();
					$ret_val[] = $image;
					break;
					
					default:
					switch(file_exists($image->getRealPath()) && ($image->setHash()))
					{
						//This image doesn't exist yet
						case false:
						$image->$idField = $model->id;
						switch($image->is_default)
						{
							//If we're replacing the default image then unset all the otehr default images
							case true:
							Image::updateAll(['is_default' => 0], [$idField => $this->id]);
							break;
						}
						$image->slug = Image::getSafeName($name)."-image-$idx";
						$imageDir = dirname($image->getRealPath());
						switch(is_dir($imageDir))
						{
							case false:
							Storage::createContainer($imageDir, true);
							break;
						}
						switch(Storage::move($uploadedFile->tempName, $image->getRealPath(), true))
						{
							case true:
							switch($image->save())
							{
								case true:
								\Yii::$app->getSession()->setFlash(
									'success',
									"Saved image ".$image->slug
								);
								self::createThumbnails($image);
								$ret_val[] = $image;
								break;
								
								default:
								\Yii::$app->getSession()->setFlash(
									'error',
									"Unable to save file informaiton to database for ".$image->slug
								);
								break;
							}
							break;
							
							default:
							\Yii::$app->getSession()->setFlash(
								'alert',
								"Unable to save physical file: ".$image->slug
							);
							break;
						}
						break;
					}
					break;
				}
				break;
			}
		}
		return $ret_val;
	}
	
	/**
	 * @param Image|string $path
	 */
	public static function createThumbnails(Image $image)
	{
		switch(file_exists($image->getRealPath()))
		{
			case true:
			$sizes = empty(static::$sizes) ? self::$_sizes : array_intersect_key(self::$_sizes, self::$sizes);
			//BaseImage::$cachePath = \Yii::getAlias('@media/cache/images');
			foreach($sizes as $size=>$options)
			{
				$basename = pathinfo($image->getRealPath(), PATHINFO_BASENAME);
				$filename = pathinfo($image->getRealPath(), PATHINFO_FILENAME);
				$basePath = DIRECTORY_SEPARATOR.$filename.DIRECTORY_SEPARATOR.$size.'-'.$basename;
				$thumbRealPath = dirname($image->getRealPath()).$basePath;
				$thumbStoredPath = dirname($image->getPath()).$basePath;
				
				if(!is_dir(dirname($thumbRealPath)))
					Storage::createContainer(dirname($thumbRealPath), true);
				
				BaseImage::thumbnail($image->getRealPath(), $options['sizeX'], $options['sizeY'])
					->save($thumbRealPath, ['quality' => $options['quality']]);
				$metadata = new ImageMetadata([
					'scenario' => 'create',
					'image_id' => $image->getId(),
					'key' => $size,
					'value' => $thumbStoredPath,
				]);
				$metadata->save();
			}
			break;
		}
	}
	
	public static function deleteImages($images)
	{
		$this->setResponseFormat('json');
		$images = (array) $images;
		foreach($images as $image)
		{
			switch($image instanceof Image)
			{
				case true:
				$metadata = $image->getMetadata()->all();
				foreach($metadata as $data)
				{
					Storage::delete($data->value);
				}
				ImageMetadata::deleteAll(['image_id' => $image->getId()]);
				if($image->delete())
					return Storage::delete($image->getPath());
				break;
			}
		}
	}
}
