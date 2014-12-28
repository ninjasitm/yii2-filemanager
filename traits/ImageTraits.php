<?php

namespace nitm\filemanager\traits;

use Yii;
use yii\helpers\Html;
use yii\web\UploadedFile;
use nitm\helpers\Icon;
use nitm\filemanager\models\Image;

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
trait ImageTraits
{	
	/**
	 * Returns the placeholder image
	 */
	public function getPlaceHolder()
	{
		return Icon::show("camera");
	}
	
	public static function getHtmlIcon($name)
	{
		switch($name)
		{
			case null:
			$name = 'camera';
			break;
			
			case 'text':
			$name = 'file-text';
			break;
		}
		return Icon::show($name, ['class' => 'fa fa-2x']);
	}
	
	/**
	 * Get the main icon for this entity
	 * @param strin $size
	 */
	public function getIcon($size='medium')
	{
		$ret_val = $this;
		switch($size)
		{
			case 'medium':
			case 'small':
			case 'large':
			case 'default':
			$size = ($size == 'default') ? 'medium' : $size;
			switch(isset($this->metadata[$size]))
			{
				case true:
				$ret_val = new Image([
					'url' => $this->metadata[$size]->value,
					'slug' => $this->metadata[$size]->key,
					'id' => $this->metadata[$size]->image_id,
				]);
				break;
			}
			break;
			
			default:
			$ret_val = new Image();
			break;
		}
		return $ret_val;
	}
	
	/**
	 * Get the main icon for this entity
	 */
	public function getIconHtml($size='small', array $options=[])
	{
		return \yii\helpers\Html::img("/image/get/".$this->getId()."/".$size, $options);
	}
	
	/**
<<<<<<< HEAD
	 * Get the main icon for this entity
	 */
	public function url($size='small')
	{
		return \Yii::$app->urlManager->createAbsoluteUrl("/image/get/".$this->getId()."/".$size);
	}
	
	public function isDefault()
	{
		return ($this->is_default == 1);
	}
}
