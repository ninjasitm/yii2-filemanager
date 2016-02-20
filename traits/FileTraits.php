<?php

namespace nitm\filemanager\traits;

use Yii;
use nitm\models\User as Users;
use nitm\filemanager\models\Image;
use nitm\filemanager\helpers\Storage;
use nitm\helpers\ArrayHelper;

/**
 * This is the traits class for files.
 */
trait FileTraits
{
	public function fields()
	{
		$src = function ($model) {
			return $model->url('large');
		};
		return [
			'id',
			'name' => 'file_name',
			'icon' => function ($model) {
				return $model->icon()->url('small');
			},
			'link' => $src,
			'url' => $src,
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
	public function getRemoteClass()
	{
		$class = $this->remote_class;
		return class_exists($class) ? $class::className() : \nitm\models\Data::className();
	}

	public function getSize()
	{
		return \Yii::$app->formatter->asShortSize($this->size);
	}

	public function getUrl($action='view')
	{
		return \Yii::$app->urlManager->createAbsoluteUrl(['', 'id' => $this->getId(), 'type' => $action]);
	}

	public function getFileExists()
	{
		return $this->exists();
	}

	public function exists()
	{
		return file_exists($this->getRealPath());
	}

	public function getRealPath()
	{
		try {
			return \Yii::getAlias($this->url);
		} catch (\Exception $e) {
			return '';
		}
	}

	public function getPath()
	{
		return $this->url('file');
	}

	public function getContents()
	{
		if(Storage::exists($this->getRealPath()))
			return Storage::getContents($this->getRealPath());
	}

	public function indexUrl($as='html', $options=[])
	{
		switch($as)
		{
			case 'modal':
			$options += ['__format' => 'modal'];
			break;

			default:
			$options += ['__format' => 'html'];
			break;
		}
		return \Yii::$app->urlManager->createAbsoluteUrl(array_merge([implode('/', [$this->isWhat(), 'index', $this->remote_type, $this->remote_id])], $options));
	}

	/**
	 * Get the main icon for this entity
	 */
	public function url($mode=null, $text=null, $url=null, $options=[])
	{
		return \Yii::$app->urlManager->createAbsoluteUrl("/files/get/".$this->geturlKey($mode));
	}

	protected function getUrlKey($mode=null)
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

	/**
	 * @param string $subject
	 */
	public static function getSafeName($subject)
	{
		$s = (!empty($s)) ? $s : array("/([^a-zA-Z0-9\\+])/", "/([^a-zA-Z0-9]){1,}$/", "/([\s]){1,}/");
		$r = (!empty($r)) ? $r : array("-", "", "-");
		return substr(strtolower(preg_replace($s, $r, $subject)), 0, 254);
	}

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMetadata()
    {
		$metadataClass = $this->getMetadataClass();
        return $this->hasMany($metadataClass, $metadataClass::metadataLink())->indexBy('key');
    }

	/**
	 * Get all the images for this entity
	 * @param boolean $thumbnails Get thumbnails as well?
	 * @param boolean $default Get the default image as well?
	 */
	public function getImages($thumbnails=false, $default=false)
	{
        return Image::getImagesFor($this, $thumbnails, $default);
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
		return $this->resolveRelation('id', Image::className(), true, [], false, 'icon');
	}

	/**
	 * Get metadata, either from key or all metadata
	 * @param string $key
	 * @return mixed
	 */
	public function metadata($key=null, $default=null)
	{
		$parts = explode('.', $key);
		$metadataClass = $this->getMetadataClass();
		$metadata = $this->resolveRelation('id', $metadataClass, true, [], true, 'metadata');
		if($key == null)
			return $metadata;
		else {
			return ArrayHelper::getValue($metadata, $key, (strpos($key, '.') === false ?  new $metadataClass : $default));
		}
	}

    /**
     * @return string
     */
    protected function getMetadataClass()
    {
		$metadataClass = static::className()."Metadata";
		switch(class_exists($metadataClass))
		{
			case false:
			$metadataClass = \nitm\filemanager\models\FileMetadata::className();
			break;
		}
		return $metadataClass;
    }
}
