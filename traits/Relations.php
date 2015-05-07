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
	public function getImages($thumbnails=false, $default=false, $limit=10)
	{
        return Image::getImagesFor($this, $thumbnails, $default, $limit);
	}
	
	public function images()
	{
		return is_array($this->images) ? $this->images : [];
	}
	
	public function imageList()
	{
		$ret_val = [];
		foreach((array)$this->images as $img)
		{
			$ret_val[] = [
				'title' => $img->file_name,
				'url' => $img->url(),
			];
		}
		return $ret_val;
	}
	
	/**
	 * Get the main icon for this entity
	 */
	public function getIcon()
	{
        return Image::getIconFor($this);
	}
	
	/**
	 * Get all the images for this entity
	 * @param boolean $thumbnails Get thumbnails as well?
	 * @param boolean $default Get the default image as well?
	 */
	public function getImages2($thumbnails=false, $default=false)
	{
        $ret_val = $this->hasMany(Image::className(), ['remote_id' => 'id']);
		$with = [];
		switch($default === true)
		{
			case false:
			$ret_val->where('`is_default`=0');
			break;
		}
		switch($thumbnails)
		{
			case true:
			$with[] = 'metadata';
			break;
		}
		$ret_val->with($with);
		$ret_val->andWhere(['remote_type' => new \yii\db\Expression("(SELECT slug FROM ".Category::tableName()." WHERE id=".new \yii\db\Expression("(SELECT type_id FROM ".$this->tableName()." WHERE id=remote_id)").")")]);
		return $ret_val;
	}
	
	/**
	 * Get the main icon for this entity
	 */
	public function getIcon2()
	{
		$key = $this->tableName() == Category::tableName() ? 'category_id' : 'content_id';
        return $this->hasOne(Image::className(), ['remote_id' => 'id'])->andWhere([
			'is_default' => 1,
			'remote_type' => new \yii\db\Expression("(SELECT slug FROM ".Category::tableName()." WHERE id=".new \yii\db\Expression("(SELECT type_id FROM ".$this->tableName()." WHERE id=remote_id)").")")
		]);
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