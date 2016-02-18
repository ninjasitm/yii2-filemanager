<?php

namespace nitm\filemanager\helpers\storage;

use Yii;
use yii\web\UploadedFile;
use nitm\helpers\ArrayHelper;

/**
 * This class helps determine where file data is saved.
 * Supports:
 *	local
 *	Amazon S3
 */
abstract class BaseStorage extends \yii\base\Object implements StorageInterface
{
	/**
	 * Constants
	 */
	const DIR_MODE = 0777;
	const FILE_MODE = 0777;

	public $dataType;

	protected $_client;
	protected $_config;

	protected $_container;
	protected $_errors = [];
	protected $_containers;


    public function init()
	{
        parent::init();
		$this->initClient();
    }

	abstract function initClient();
	abstract function exists($id);
	abstract function getContainers();

	public function getContainer($file=null)
	{
		$container = $this->extractContainer($file);
		return $container ?: $this->_container;
	}

	public function setContainer($container)
	{
		$this->_container = $this->extractContainer($container);
	}

	public function containerExists($container)
	{
		return ArrayHelper::exists($this->getContainers($container), $this->getContainer($container));
	}

	public function getClient()
	{
		return $this->_client;
	}

	public function setClient($client)
	{
		if($client instanceof $this->clientClass)
			$this->_client = $client;
		else
			throw new \yii\base\InvalidArgumentException("Client should be of class ".$this->clientClass);
	}

	public function setConfig($contig)
	{
		$this->_config = $config;
	}

	public function getErrors()
	{
		return $this->_errors;
	}

	public function setErrors($errors, $merge=false)
	{
		if($merge)
			$this->_errors = array_merge($this->_errors, $errors);
		else
			$this->_errors = $errors;
	}

	abstract public function getUrl($of);

	public function getAlias($of)
	{
		return \Yii::getAlias($of);
	}

	/**
	 * [beforeSave description]
	 * @param  File|string $file  The file being uploaded
	 * @param  string $name  The name of the file
	 * @param  boolean $thumb Should a thumb be created?
	 * @param  string $path  The relative path of the file
	 * @param  string $type  The type of the file
	 * @return array        The path, full path and name of the file
	 */
	protected function beforeAction($file, $name = null, $thumb=false, $path=null, $type=null)
	{
		$filePath = is_string($file) ? $file : $file->url;
        if(empty($path)) {
            $path = ArrayHelper::getValue(\Yii::$app->getModule('nitm-files')->path, $filePath, '');
        }

        if(is_null($name)){
            $name = basename($filePath);
        }

        if($thumb){
            $path = rtrim($path, '/').'/thumbs/';
        }

		$this->_container = $this->getContainer($path.$name);

		return [$this->_container, $path, substr($path.$name, strlen($this->_container)+1), $name, $filePath];
	}

	/**
	 * This extracts the top level directory in the provided file hierarchy
	 * @param  string | File $file [description]
	 * @return string       [description]
	 */
	private function extractContainer($file)
	{
		$container = false;
		if($file !== null) {
			$filePath = is_object($file) ? $file->url : $file;
			$options = ltrim(parse_url($filePath, PHP_URL_PATH), '/');
			$container = substr($options, 0, strpos($options, '/'));
			$container = \yii\helpers\Inflector::slug(substr($container, 0, 63));
		}
		return $container;
	}
}
