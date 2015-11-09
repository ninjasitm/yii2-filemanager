<?php

namespace nitm\filemanager\traits;

use Yii;
use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\web\UploadedFile;
use nitm\helpers\Icon;
use nitm\filemanager\models\Image;

/**
 * This is the model class for table "images".
 *
 * @property integer $id
 * @property integer $remote_id
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
	public function getIconNew($size='medium')
	{
		return $this->hasOne(\nitm\filemanager\models\ImageMetadata::className(), ['image_id', 'id'])
			->where([
				'key' => $size
			]);
	}

	/**
	 * Get the main icon for this entity
	 * @param strin $size
	 */
	public function getIcon($size='medium')
	{
		$ret_val = $this;
		$size = is_null($size) ? 'large' : $size;
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
					'width' => $this->metadata[$size]->width,
					'height' => $this->metadata[$size]->height,
					'size' => $this->metadata[$size]->size,
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
	 * Dynamically update a thumb for an image
	 */
	public function updateSizes()
	{
		if(!$this->height || !$this->width && (strlen($this->getRealPath())>0)) {
			list($x, $y, $size) = $this->getImageSize();
			$this->height = $x;
			$this->width = $y;
			$this->size = $size;
			$this->save();
		}
	}

	public function updateMetadataSizes($size='medium')
	{
		$metadata = ArrayHelper::getValue($this->metadata, $size, null);
		if($metadata == null)
			return;
		if(!$metadata->height || !$metadata->width && (strlen(\Yi::getAlias($metadata->value))>0)) {
			list($x, $y, $size) = $this->getImageSize($size);
			$metadata->setScenario('update');
			$metadata->height = $x;
			$metadata->width = $y;
			$metadata->size = $size;
			$metadata->save();
		}
	}

	/**
	 * Utry two methods of getting the height information for this image
	 */
	public function getImageSize($metadataSize=null)
	{
		$path = is_null($metadataSize) ? $this->getRealPath() : ArrayHelper::getValue($this->metadata, $metadataSize.'.value', $this->getRealPath());

		if(!file_exists($path))
			return [0, 0, 0];

		try {
			list($x, $y, $size) = getimagesize($path);
		} catch (\Exception $e) {
			if(file_exists($path)) {
				$arrContextOptions = [
					"ssl" => [
						"verify_peer" => false,
						"verify_peer_name" => false,
					],
				];
				list($x, $y, $size) = getimagesizefromstring(file_get_contents($path, false, stream_context_create($arrContextOptions)));
			} else {
				$x = $y = $size = 0;
			}
		}
		return [$x, $y, $size];
	}

	/**
	 * Get the main icon for this entity
	 */
	public function getIconHtml($size='small', array $options=[], $mode='raw')
	{
		$id = $mode=='name' ? $this->file_name : $this->getId();
		return \yii\helpers\Html::img($this->url($size, $mode), $options);
	}

	/**
	 * Get the main icon for this entity
	 */
	public function getIconRaw($size='small', $mode=null)
	{
		$id = $mode =='name' ? $this->file_name : $this->getId();
		return $this->url($size, $mode);
	}

	/**
	 * Get the main icon for this entity
	 */
	public function url($size='medium', $mode=null)
	{
		//Compensate for finding metadata icon urls here
		$url = ArrayHelper::getValue($this->metadata, $size.'.value', ArrayHelper::getValue($this, 'url', ArrayHelper::getValue($this->metadata, 'medium')));
		if(file_exists(\Yii::getAlias($url)))
			return \Yii::$app->urlManager->createAbsoluteUrl(["/image/get/".$this->geturlKey($size), 'size' => $size]);
		else
			return $url;
	}

	public function isDefault()
	{
		return ((bool)$this->is_default === true);
	}

	protected function getUrlKey($size='medium', $mode=null)
	{
		$ret_val = '';
		switch($mode)
		{
			case 'remote':
			$ret_val = implode(':', [$this->remote_type, $this->remote_id]).'/'.$this->file_name;
			break;

			case 'file':
			$ret_val = $this->file_name;
			break;

			default:
			$ret_val = $this->getId().'/'.$this->file_name;
			break;
		}
		return $ret_val;
	}
}
