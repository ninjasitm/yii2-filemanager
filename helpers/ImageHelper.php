<?php

namespace nitm\filemanager\helpers;

use Yii;
use yii\helpers\Html;
use yii\helpers\Inflector;
use yii\helpers\FileHelper;
use yii\web\UploadedFile;
use yii\imagine\Image as BaseImage;
use kartik\icons\Icon;
use nitm\filemanager\helpers\Storage;
use nitm\filemanager\models\Image;
use nitm\filemanager\models\ImageMetadata;
use nitm\helpers\ArrayHelper;
use nitm\helpers\Network;
use Imagick;

/**
 * Image class helper provides some useful functionality for handling and saving images.
 */
class ImageHelper
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
			'size-x' => 256,
			'size-y' => 256,
			'quality' => 90
		],
		'medium' => [
			'size-x' => 512,
			'size-y' => 512,
			'quality' => 90
		],
		'large' => [
			'size-x' => 1024,
			'size-y' => 1024,
			'quality' => 90
		]
	];

	public static function getDirectory($getAlias=false)
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
	public static function saveImages($model, $name, $id=null, $instanceName=null, $uploads=null)
	{
		$imageModel = $model instanceof Image ? $model : $model->image();
		$instanceName = $instanceName ?: 'file_name';
		$uploads = $uploads ?: UploadedFile::getInstances($imageModel, $instanceName);
		if($uploads == [])
			$uploads = UploadedFile::getInstancesByName($instanceName);
		return self::saveInternally($imageModel, $uploads, [
			'name' => $name,
			'id' => $id
		]);
	}

	public static function saveFromStdIn($model, $name, $id, $uploads=null)
	{
		if(!is_array($uploads)) {
			$file = UploadHelper::getDataFromStdIn();
			if(is_null($file))
				return false;
			$uploads = [$file];
		}
		$imageModel = $model instanceof Image ? $model : $model->image();
		return self::saveInternally($imageModel, $uploads, [
			'name' => $name,
			'id' => $id
		]);
	}

	/**
	 * @param Image|string $path
	 */
	public static function createThumbnails(Image $image, $type, $file=null)
	{
		if($image->isNewRecord) {
			$image->on(\yii\db\ActiveRecord::EVENT_AFTER_INSERT, function ($event) {
				static::createThumbnails($event->sender, $event->sender->type);
			});
			return false;
		}
		switch(file_exists($image->getRealPath()) || filter_var($image->url, FILTER_VALIDATE_URL))
		{
			case true:
			$metadatas = $image->metadata;
			$sizes = empty(static::$sizes) ? self::$_sizes : array_intersect_key(self::$_sizes, self::$sizes);
			//BaseImage::$cachePath = \Yii::getAlias('@media/cache/images');
			foreach($sizes as $size=>$options)
			{
				$file = is_null($file) ?  $image->getRealPath() : $file;
				list($thumbStoredPath, $baseName, $fileName, $basePath) = static::getStoredPath($file, $size);

				if(!filter_var($image->url, FILTER_VALIDATE_URL) && !Storage::containerExists(dirname($thumbStoredPath), 'image'))
					Storage::createContainer(dirname($thumbStoredPath), true, [], 'image');
				/**
				 * To save we're using ob contents to get the outputed image from memory
				 */
				//Here we create proportional images
			    $imagine = BaseImage::getImagine()->load(file_get_contents($image->getRealPath()));
			    $box = $imagine->getSize();
				if($options['size-x'] < $box->getWidth())
					$box = $box->widen($options['size-x']);
				else
					$box =$box->heighten($options['size-y']);
				$thumb = $imagine->resize($box);
				$imageType = explode('/', $image->type);
				$thumbnail = $thumb->get(array_pop($imageType), [
						'quality' => $options['quality'],
						'format' => pathinfo($thumbStoredPath, \PATHINFO_EXTENSION)
					]);

				/**
				 * The Storage engine should understand how to save a stream to a file;
				 */
				$url = Storage::move($thumbnail, $thumbStoredPath, !\nitm\helpers\Network::isValidUrl($file), true, 'image');

				if(!\nitm\helpers\Network::isValidUrl($thumbStoredPath) && file_exists(\Yii::getAlias($thumbStoredPath)))
					$url = $thumbStoredPath;

				$metadata = ArrayHelper::getValue($metadatas, $size, new ImageMetadata([
					'scenario' => 'create',
					'image_id' => $image->getId(),
					'key' => $size,
					'value' => $url,
				]));

				$metadataAttrs = [
					'width' => $thumb->getSize()->getWidth(),
					'height' => $thumb->getSize()->getHeight(),
					'size' => static::getImageBlobFileSize($thumb)
				];
				$metadata->setScenario($metadata->isNewRecord ? 'create' : 'update');
				$metadata->setAttributes($metadataAttrs);
				$metadata->save();
				$metadatas[$size] = $metadata;
			}
			$image->populateRelation('metadata', $metadatas);
			break;
		}
	}

	protected static function getStoredPath($file, $size=null)
	{
		if(\nitm\helpers\Network::isValidUrl($file)) {
			$file = parse_url($file, PHP_URL_PATH);
		}
		$fileName = pathinfo($file, PATHINFO_BASENAME);
		$baseName = pathinfo($file, PATHINFO_FILENAME);
		$basePath = DIRECTORY_SEPARATOR;
		if($size !== null) {
			$basePath .= 'thumbs'.DIRECTORY_SEPARATOR.$baseName.DIRECTORY_SEPARATOR.$size.'-';
		}
		$basePath .= $fileName;
		return [dirname($file).$basePath, $basePath, $baseName, $fileName];
	}

	public static function deleteImages($images)
	{
		$images = is_object($images) ? [$images] : $images;
		foreach($images as $image) {
			static::deleteImage($image);
		}
		return true;
	}

	public static function deleteImage($image)
	{
		if($image instanceof Image) {
			$metadata = $image->getMetadata()->all();
			foreach($metadata as $data) {
				Storage::delete($data->value, 'image');
			}
			ImageMetadata::deleteAll(['image_id' => $image->id]);
			if($image->delete())
				return Storage::delete($image->getPath(), 'image');
		}
		return false;
	}

	public static function getImageBlobFileSize($blob)
	{
		if (function_exists('mb_strlen')) {
		    $size = mb_strlen($blob, '8bit');
		} else {
		    $size = strlen($blob);
		}
		return $size;
	}

	protected static function saveInternally($imageModel, $uploads, $options=[])
	{
		$ret_val = [];
		$saveOptions = [
			'method' => 'move'
		];
		extract($options);
		$pathId = md5(is_null($id) ? uniqid() : $id);
		$name = Image::getSafeName($name);
		//Increase the count for the images for this model $model
		$idx = ($count = $imageModel->getCount()->one()) != null ? $count->count() : 0;
		foreach($uploads as $uploadedFile)
		{
			if($uploadedFile->hasError) {
				$ret_val[] = new Image();
				continue;
			}
			if($uploadedFile->type == Image::URL_MIME) {
				list($uploadedFile, $size) = UploadHelper::getFromUrl(file_get_contents($uploadedFile->tempName));
			} else {
				$size = getimagesize($uploadedFile->tempName);
			}
			//We're counting starting from 1
			$idx++;
			$image = new Image(['scenario' => 'create']);
			$directory = rtrim(implode(DIRECTORY_SEPARATOR, array_filter([rtrim(static::getDirectory(), DIRECTORY_SEPARATOR), $name, $id])), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
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
				'url' => $directory.implode('-', array_filter([Inflector::slug($uploadedFile->baseName), md5($uploadedFile->name)])).".".$uploadedFile->extension,
				'is_default' => self::getIsDefault($imageModel, $uploadedFile, $idx),
				'size' => $uploadedFile->size
			], false);

			$originalPath = $image->url;
			$tempImage = new Image([
				'url' => $uploadedFile->tempName,
				'type' => $uploadedFile->type
			]);

			$existing = Image::find()->where([
				"hash" => $image->hash,
				'remote_type' => $name,
				'remote_id' => $id
			])->one();

			if($existing instanceof Image) {
				//If an image already exists for this file then swap images
				$image = $existing;
				\Yii::trace("Found dangling $name image ".$image->slug);
				$tempImage->id = $image->getId();
				self::createThumbnails($image, $image->type, $image->getRealPath());
				$existing->remote_id = $id;
				$existing->save();
				$ret_val[] = $image;
			} else {
				if(!Storage::exists($image->url, 'image') && $image->hash) {
					//This image doesn't exist yet
					$image->remote_id = $id;
					if($image->is_default) {
						//If we're replacing the default image then unset all the otehr default images
						Image::updateAll(['is_default' => false], [
							'remote_id' => $id,
							'remote_type' => $name
						]);
					}
					$imageDir = dirname($image->getRealPath());

					if(!Storage::containerExists($imageDir, 'image'))
						Storage::createContainer($imageDir, true, [], 'image');

					$url = Storage::save($tempImage, $image->getRealPath(), [], false, $image->getRealPath(), 'image');

					if(filter_var($url, FILTER_VALIDATE_URL)) {
						$proceed = true;
						$image->url = $url;
					} else if(Storage::exists($image->getRealPath(), 'image'))
						$proceed = true;
					else
						$proceed = false;

					if($proceed) {
						if($image->save()) {
							\Yii::trace("Saved image ".$image->slug);
							/**
							 * Need top fix creating thumbnail sbefore uploading to AWS
							 */
							$tempImage->setAttributes([
								'id' => $image->getId(),
								'remote_id' => $image->remote_id,
								'remote_type' => $image->remote_type
							]);
							$tempImage->setOldAttributes($image->getAttributes());
							$tempImage->id = $image->id;
							self::createThumbnails($tempImage, $image->type, $image->url);
							$image->populateRelation('metadata', $tempImage->metadata);
							$ret_val[] = $image;
						} else {
							\Yii::error("Unable to save file informaiton to database for ".$image->slug."\n".json_encode($image->getErrors()));
						}
					} else {
						\Yii::trace("Unable to save physical file: ".$image->slug);
					}
				} else {
					//This image exists already lets attach it and update the thumbnails if necessary.
					if($image->save()){
						$ret_val[] = $image;
						self::createThumbnails($tempImage, $image->type, $image->url);
					}
				}
			}
			if(!Network::isValidUrl($uploadedFile->tempName))
				unlink($uploadedFile->tempName);
		}
		return $ret_val;
	}

	protected static function getIsDefault($model, $file, $idx)
	{
		$uploadedName = ArrayHelper::getValue($_FILES, $model->formName().'.tmp_name.images.default', ($idx === 0));
		if($uploadedName && ($uploadedName === $file->tempName))
			return true;
		else
			return $model->count() == 0;
	}
}
