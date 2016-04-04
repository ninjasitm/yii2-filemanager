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
	public function fields()
	{
		$src = function ($model) {
			return $model->url('large');
		};
		$icon = function ($model) {
			return $model->getIcon()->url('medium');
		};
		return [
			'id',
			'name' => 'file_name',
			'icon' => $icon,
			'thumb' => $icon,
			'image' => $src,
			'url' => $src,
			'src' => $src,
			'title' => 'file_name',
			'size' => function ($model) {
				return $model->getSize();
			}
		];
	}

	public function extraFields()
	{
		return [];
	}

	/**
	 * Returns the placeholder image
	 */
	public function getPlaceHolder()
	{
		return Icon::show("camera");
	}

	public static function getHtmlIcon($options, $size='2x')
	{
		$provider = 'fa';
		if(is_array($options)) {
			$name = ArrayHelper::remove($options, 'icon', 'camera');
			$provider = ArrayHelper::remove($options, 'provider', $provider);
		} else
			$name = $options;

		$name = $name ?: 'camera';

		$hasSpecial = preg_match('/[&;]/', $name);

		if($hasSpecial === 1)
			return Html::tag('i', $name, ['class' => 'icon icon-'.$size]);

		$options = array_merge([
			'class' => implode(' ', [$provider, $provider.'-'.$size, $provider.'-'.$name])
		], (array) $options);
		return Html::tag('i', '', $options);
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
			$metadata = $this->metadata($size);
			switch($metadata->isNewRecord)
			{
				case false:
				$ret_val = new Image([
					'url' => $metadata->value,
					'slug' => $metadata->key,
					'id' => $metadata->image_id,
					'width' => $metadata->width,
					'height' => $metadata->height,
					'size' => $metadata->size,
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
		$metadata = $this->metadata($size);
		if($metadata->isNewRecord)
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
				//We use a value of 1 to prevent rechecking of metadata for this image
				$x = $y = $size = 1;
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
		$url = ArrayHelper::remove($options, 'url', $this->url($size, $mode));
		return \yii\helpers\Html::img($url, $options);
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
	public function url($size='full', $mode=null, $url=null, $options=[])
	{
		//Compensate for finding metadata icon urls here
		if($size === 'full')
			return \Yii::getAlias($this->url);
		else {
			if($this->id) {
				$url = $this->metadata($size.'.value', ArrayHelper::getValue($this, 'url'));
				try {
					$path = \Yii::getAlias($url);
				} catch (\Exception $e) {
					$path = '';
				}
				if(file_exists($path))
					return \Yii::$app->urlManager->createAbsoluteUrl(["/image/get/".$this->geturlKey($size), 'size' => $size]);
				else
					return $url;
			} else {
				return null;
			}
		}
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
