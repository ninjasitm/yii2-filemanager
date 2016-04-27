<?php

namespace nitm\filemanager\models;

use Yii;
use yii\base\Exception;
use yii\base\Model;
use yii\base\Event;
use yii\db\ActiveRecord;
use nitm\widgets\models\Data;
use nitm\widgets\User;
use nitm\widgets\models\security\Fingerprint;
use nitm\interfaces\DataInterface;
use nitm\helpers\Cache;

/**
 * Class BaseWidget
 * @package nitm\widgets\models
 *
 */

class BaseFile extends \nitm\models\Entity
{
	use \nitm\traits\RemoteRelations, \nitm\filemanager\traits\FileTraits {
		\nitm\filemanager\traits\FileTraits::fields insteadof \nitm\traits\RemoteRelations;
		\nitm\filemanager\traits\FileTraits::extraFields insteadof \nitm\traits\RemoteRelations;
	}

	protected $link = [
		'remote_type' => 'remote_type',
		'remote_id' => 'remote_id'
	];

	const URL_MIME = 'file/url';

	public function init()
	{
		$this->setConstraints($this->constrain);
		$this->addWith(['author']);
		if($this->initSearchClass)
			//static::initCache($this->constrain, self::cacheKey($this->getId()));

		if(is_object(static::currentUser()))
			static::$userLastActive = date('Y-m-d G:i:s', strtotime(is_null(static::$userLastActive) ? static::currentUser()->lastActive() : static::$userLastActive));
	}

	public function scenarios() {
		return array_merge(parent::scenarios(), [
			'create' => ['remote_id', 'remote_type', 'size', 'hash', 'url', 'file_name', 'title', 'slug', 'html_icon', 'base_type', 'type'],
			'update' => ['remote_id', 'remote_type', 'size', 'hash', 'url', 'file_name', 'title', 'slug', 'html_icon', 'base_type', 'type'],
			'delete' => ['remote_id', 'remote_type']
		]);
	}

	public static function has()
	{
		$has = [
			'author' => null,
			'editor' => null,
			'hidden' => null,
			'deleted' => null,
		];
		return array_merge(parent::has(), $has);
	}

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFileTerms()
    {
        return $this->hasMany(FileTerms::className(), ['file_id' => 'id']);
    }

	/**
	 * @param string file
	 */
	public function getHash($file=null)
	{
		if(!$file)
			$file = $this->url;
		try {
			$file = \Yii::getAlias($file);
		} catch (\Exception $e) {}
		$parts = [];
		if(file_exists($file))
			$parts = explode(' ', @exec("md5sum '".$file."'"));
		return array_shift($parts);
	}

	public function setHash($hash=null)
	{
		$this->hash = !$hash ? $this->getHash() : $hash;
		return (bool)!is_null($this->hash);
	}

	/**
	 * Add metadata for an image item
	 * @param mixed $array
	 * @return boolean
	 */
	public function addMetadata($array)
	{
		$ret_val = false;
		switch(is_array($array))
		{
			case true:
			$ret_val = true;
			$metadataClass = $this->getMetadataClass();
			$currentMetadata = $this->getMetadata()->indexBy('id')->all();
			foreach($array as $key=>$value)
			{
				$id = isset($value['id']) ? $value['id'] : null;
				switch(!is_null($id) && isset($currentMetadata[$id]))
				{
					case true:
					$metadata = $currentMetadata[$id];
					break;

					default:
					$metadata = new $metadataClass;
					break;
				}
				$metadata->reomte_id = $this->id;
				$metadata->key = $key;
				$metadata->value = $value;
				$metadata->save();
			}
			break;
		}
		return $ret_val;
	}

	/**
	 * Get all the files for this entity
	 * @param boolean $metadata Get metadata as well?
	 */
	public static function getFilesFor($model, $metadata=false)
	{
        $ret_val = $model->hasMany(File::className(), ['remote_id' => 'id']);
		$with = [];
		switch($metadata)
		{
			case true:
			$with[] = 'metadata';
			break;
		}
		$ret_val->with($with);
		$ret_val->andWhere(['remote_type' => $model->isWhat()]);
		return $ret_val;
	}

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser($options=[])
    {
        return $this->hasOne(\nitm\models\User::className(), ['id' => 'user_id']);
    }
}
?>
