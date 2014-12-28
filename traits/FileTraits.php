<?php

namespace nitm\filemanager\traits;

use Yii;
use nitm\models\User as Users;
use nitm\filemanager\models\Image;

/**
 * This is the traits class for files.
 */
trait FileTraits
{
	public function getSize()
	{
		return \yii::$app->formatter->asShortSize($this->size);
	}
	
	public function getUrl($action='view')
	{
		return \Yii::$app->urlManager->createAbsoluteUrl(['', 'id' => $this->getId(), 'type' => $action]);
	}
	
	public function getFileExists()
	{
		return file_exists($this->getRealPath());
	}
	
	public function getRealPath()
	{
		return \Yii::getAlias($this->url);
	}
	
	public function getPath()
	{
		return $this->url;
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
    public function getType()
    {
        return $this->hasOne(static::className(), ['id' => 'type_id']);
    }
	
	public function type()
	{
		return $this->type instanceof static ? $this->type : new static;
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
        $ret_val = $this->hasMany(Image::className(), ['content_id' => 'id']);
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
		$ret_val->andWhere(['remote_type' => $this->isWhat()]);
		return $ret_val;
	}
	
	/**
	 * Get the main icon for this entity
	 */
	public function getIcon()
	{
        return $this->hasOne(Image::className(), ['remote_id' => 'id'])->where([
			'is_default' => 1,
			'remote_type' => $this->isWhat()
		]);
	}
		
	/**
	 * Get metadata, either from key or all metadata
	 * @param string $key
	 * @return mixed
	 */
	public function icon()
	{
		return \nitm\helpers\Relations::getRelatedRecord('icon', $this, Image::className());
	}	
		
	/**
	 * Get metadata, either from key or all metadata
	 * @param string $key
	 * @return mixed
	 */
	public function metadata($key=null)
	{
		return !is_null($key) ? (isset($this->metadata[$key]) ? $this->metadata[$key] : '') : $this->metadata;
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
			$metadataClass = EntityMetadata::className();
			break;
		}
		return $metadataClass;
    }
}
