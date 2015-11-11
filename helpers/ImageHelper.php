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
			'sizeX' => 128,
			'sizeY' => 128,
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
		$dir = \Yii::$app->getModule('nitm-files')->getPath('image');
		if($getAlias)
			$dir = \Yii::getAlias($dir);
		return $dir;
	}

	/**
	 * Save uploaded images
	 * @param Entity $model The model images are being saved for
	 * @param string $name
	 */
	public static function saveImages($model, $name, $id=null)
	{
		// retrieve all uploaded files for name images
		$ret_val = [];
		$pathId = md5(is_null($id) ? uniqid() : $id);
		$name = Image::getSafeName($name);
		$imageModel = $model instanceof Image ? $model : $model->image();
		$uploads = UploadedFile::getInstances($imageModel, 'file_name');
		//Increase the count for the images for this model $model
		$idx = ($count = $imageModel->getCount()->one()) != null ? $count->count() : 0;
		foreach($uploads as $uploadedFile)
		{
			$idx++;
			$image = new Image(['scenario' => 'create']);
			$directory = rtrim(implode(DIRECTORY_SEPARATOR, array_filter([rtrim(static::getDirectory(), DIRECTORY_SEPARATOR), $name, $id])), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
			$size = getimagesize($uploadedFile->tempName);

			$image->setAttributes([
				'width' => $size[0] || 0,
				'height' => $size[1] || 0,
				'type' => $uploadedFile->type,
				'author_id' => \Yii::$app->user->getIdentity()->getId(),
				'file_name' => $uploadedFile->name,
				'remote_type' => $name,
				'remote_id' => $id,
				'slug' => Image::getSafeName($name)."-$id-image-$idx",
				'hash' => Image::getHash($uploadedFile->tempName),
				'url' => $directory.implode('-', array_filter([($image->is_default ?  'default' : 'extra'), "image", md5($uploadedFile->name)])).".".$uploadedFile->getExtension(),
				'is_default' => @($_FILES[$model->formName()]['tmp_name']['images']['default'] == $uploadedFile->tempName) ? true : false,
				'size' => $uploadedFile->size
			], false);

			$originalPath = $image->url;
			$tempImage = new Image([
				'url' => $uploadedFile->tempName,
				'type' => $uploadedFile->type
			]);

			$existing = Image::find()->where([
				"hash" => $image->hash,
				'remote_type' => $name
			])->one();

			switch($existing instanceof Image)
			{
				//If an image already exists for this file then swap images
				case true:
				$image = $existing;
				\Yii::trace("Found dangling $name image ".$image->slug);
				$tempImage->id = $image->getId();
				self::createThumbnails($image, $image->type, $image->getRealPath());
				$existing->remote_id = $id;
				$existing->save();
				$ret_val[] = $image;
				break;

				default:
				switch(file_exists($image->getRealPath()) && ($image->setHash()))
				{
					//This image doesn't exist yet
					case false:
					$image->remote_id = $id;
					switch($image->is_default)
					{
						//If we're replacing the default image then unset all the otehr default images
						case true:
						Image::updateAll(['is_default' => false], [
							'remote_id' => $id,
							'remote_type' => $name
						]);
						break;
					}
					$imageDir = dirname($image->getRealPath());

					if(!is_dir($imageDir))
						Storage::createContainer($imageDir, true, [], 'image');

					$url = Storage::save($tempImage, $image->getRealPath(), [], false, $image->getRealPath(), 'image');

					if(filter_var($url, FILTER_VALIDATE_URL)) {
						$proceed = true;
						$image->url = $url;
					} else if(file_exists($image->getRealPath()))
						$proceed = true;
					else
						$proceed = false;

					if($proceed)
					{
						if($image->save()) {
							\Yii::trace("Saved image ".$image->slug);
							/**
							 * Need top fix creating thumbnail sbefore uploading to AWS
							 */
							$tempImage->id = $id;
							self::createThumbnails($image, $image->type, $originalPath);
							$ret_val[] = $image;
						} else {
							\Yii::trace("Unable to save file informaiton to database for ".$image->slug);
						}
					} else {
						\Yii::trace("Unable to save physical file: ".$image->slug);
					}
					break;

					//This image exists already lets attach it.
					default:
					if($image->save()) {
						$ret_val[] = $image;
						self::createThumbnails($image, $image->type, $image->url);
					}
					break;
				}
				break;
			}
			unlink($uploadedFile->tempName);
		}
		return $ret_val;
	}

	/**
	 * @param Image|string $path
	 */
	public static function createThumbnails(Image $image, $type, $file=null)
	{
		switch(file_exists($image->getRealPath()) || filter_var($image->url, FILTER_VALIDATE_URL))
		{
			case true:
			$sizes = empty(static::$sizes) ? self::$_sizes : array_intersect_key(self::$_sizes, self::$sizes);
			//BaseImage::$cachePath = \Yii::getAlias('@media/cache/images');
			foreach($sizes as $size=>$options)
			{
				$file = is_null($file) ?  $image->getRealPath() : $file;
				$basename = pathinfo($file, PATHINFO_BASENAME);
				$filename = pathinfo($file, PATHINFO_FILENAME);
				$basePath = DIRECTORY_SEPARATOR.$filename.DIRECTORY_SEPARATOR.$size.'-'.$basename;
				$thumbRealPath = dirname($file).$basePath;
				$thumbStoredPath = dirname($file).$basePath;

				if(!filter_var($image->url, FILTER_VALIDATE_URL) && !is_dir(dirname($thumbRealPath)))
					Storage::createContainer(dirname($thumbRealPath), true, [], 'image');
				/**
				 * To save we're using ob contents to get the outputed image from memory
				 */
				$thumb = BaseImage::thumbnail($image->getRealPath(), $options['sizeX'], $options['sizeY'])
					->get(array_pop(explode('/', $image->type)), [
						'quality' => $options['quality'],
						'format' => pathinfo($thumbStoredPath, \PATHINFO_EXTENSION)
					]);

				/**
				 * The Storage engine should understand how to save a stream to a file;
				 */
				$url = Storage::move($thumb, $thumbStoredPath, false, true, $type, 'image');

				$imageSize = @getimagesize(Yii::getAlias($thumbStoredPath));

				if(file_exists(\Yii::getAlias($thumbStoredPath)))
					$url = $thumbStoredPath;

				$metadata = new ImageMetadata([
					'scenario' => 'create',
					'image_id' => $image->getId(),
					'key' => $size,
					'value' => $url,
					'width' => @$imageSize[0],
					'height' => @$imageSize[1],
					'size' => filesize(\Yii::getAlias($thumbStoredPath))
				]);
				$metadata->save();
			}
			break;
		}
	}

	public static function deleteImages($images)
	{
		$images = is_object($images) ? [$images] : $images;
		foreach($images as $image)
		{
			static::deleteImage($image);
		}
		return true;
	}

	public static function deleteImage($image)
	{
		if($image instanceof Image) {
			$metadata = $image->getMetadata()->all();
			foreach($metadata as $data)
			{
				Storage::delete($data->value);
			}
			ImageMetadata::deleteAll(['image_id' => $image->getId()]);
			if($image->delete())
				return Storage::delete($image->getPath());
		}
		return false;
	}
}
