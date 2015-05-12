<?php

namespace nitm\filemanager\traits;

use Yii;
use yii\base\Model;
use yii\base\Event;
use nitm\models\User;
use nitm\widgets\models\Category;
use nitm\filemanager\models\Image;

/**
 * Class Replies
 * @package nitm\module\models
 */

trait Relations
{
	
	/**
	 * Get all the images for this entity
	 * @param boolean $thumbnails Get thumbnails as well?
	 * @param boolean $default Get the default image as well?
	 */
	public function getImages($thumbnails=false, $default=false)
	{
        return Image::getImagesFor($this, $thumbnails, $default);
	}
	
	public function imageList()
	{
		return array_map(function ($image) {
			return [
				'title' => ucfirst($image->remote_type).' Image',
				'thumb' => $image->getIcon('medium')->url,
				'url' => $image->url,
				//s'description' => $image->metadata->description
			];
		}, $this->images);
	}
	
	/**
	 * Get the main icon for this entity
	 */
	public function getIcon()
	{
        return Image::getIconFor($this);
	}
		
	/**
	 * Get metadata, either from key or all metadata
	 * @param string $key
	 * @return mixed
	 */
	public function icon()
	{
		return $this->icon instanceof Image ? $this->icon : new Image();
	}	
}
?>