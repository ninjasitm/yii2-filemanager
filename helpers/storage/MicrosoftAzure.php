<?php

namespace nitm\filemanager\helpers\storage;

use yii\helpers\Inflector;
use nitm\filemanager\models\File;
use nitm\helpers\Cache;
use nitm\helpers\ArrayHelper;
use WindowsAzure\Common\ServicesBuilder;
use WindowsAzure\Blob\Models\CreateContainerOptions;
use WindowsAzure\Blob\Models\ListBlobsOptions;
use WindowsAzure\Blob\Models\PublicAccessType;
use WindowsAzure\Common\ServiceException;

/**
 * Kicrosoft Azure stroage wrapper.
 */

class MicrosoftAzure extends BaseStorage implements StorageInterface
{
	protected $clientClass = "\WindowsAzure\Blob\Internal\IBlob";

    public function init()
	{
        parent::init();

    }

    public function initClient()
	{
		if(!isset($this->client))
		{
			$this->_config = \Yii::$app->getModule('nitm-files')->setting('azure.cdn');

			if($this->_config)
			{
				if($this->_config['AccountName'] == ''){
					throw new InvalidConfigException('Account name cannot be empty!');
				}
				if($this->_config['AccountKey'] == ''){
					throw new InvalidConfigException('Secret cannot be empty!');
				}

				$connectionString = ArrayHelper::splitc(array_merge([
					'DefaultEndpointsProtocol' => ['https'],
				], $this->_config), null, '=', ';');
				$this->client = ServicesBuilder::getInstance()
									->createBlobService($connectionString);
			}
		}
    }

	public function getContainers($specifically=null)
	{
		if(!isset($this->_containers))
		{
			$this->_containers = [];
			$list = $this->client->listContainers();
			foreach($list->getContainers() as $container)
				$this->_containers[$container->getName()] = $container->getName();
		}
		return $this->_containers;
	}

    public function save($file, $name = null, $thumb=false, $path=null, $type=null)
	{
		list($container, $path, $relativePath, $name, $filePath) = parent::beforeAction($file, $name, $thumb, $path, $type);

		$url = false;
		try {
		    //Upload blob
			if(@file_exists($filePath))
				$contents = fopen($filePath, 'r');
			else if(is_string($filePath))
				$contents = $filePath;
			else
				$contents = false;

			if($contents) {
				if(!$this->containerExists($container))
					$this->createContainer($container);
			    $result = $this->client->createBlockBlob($container, $relativePath, $contents);
				$url = $this->getUrl($container.'/'.$relativePath);
			}
		}
		catch(ServiceException $e){
			\Yii::warning($e->getMessage());
		    // Handle exception based on error codes and messages.
		    // Error codes and messages are here:
		    // http://msdn.microsoft.com/library/azure/dd179439.aspx
		    $this->setErrors([
				'code' => $e->getCode(),
				'message' => $e->getMessage()
			]);
			return false;
		}

		return $url ? $url : false;

    }

	protected function blobName($file)
	{
		$filePath = is_object($file) ? $file->url : $file;
		$options = parse_url($filePath, PHP_URL_PATH);
		return substr($options, strlen($this->getContainer(basename($filePath)))+1);
	}

    public function delete($files)
	{
		$files = !is_array($files) ? [$files] : $files;
		foreach($files as $file) {
			try {
				if($this->exists($file->getRealPath()))
					$this->client->deleteBlob($this->getContainer($file), $this->blobName($file));
			} catch (ServiceException $e) {
				\Yii::warning($e->getMessage());
			    $this->setErrors([
					'code' => $e->getCode(),
					'message' => $e->getMessage()
				], true);
			}
		}
		return true;
    }

	public function move($from, $to, $isUploaded=false, $thumb=false, $type=null)
	{
		try {
			if(is_string($from))
				$from = new \nitm\filemanager\models\File([
					'url' => $from,
					'type' => (is_null($type) ? ArrayHelper::getValue(getImageSize($from), 'mime', 'binary/octet-stream') : $type)
				]);
		} catch(\Exception $e) {
			\Yii::warning($e->getMessage());
		}

		$ret_val = $this->save($from, basename($to), $thumb, dirname($to), $type);

		if($ret_val) {
			$this->delete($from);
		}

		return $ret_val;
	}

	/**
	 * Does $path Exist?
	 * @param string $path
	 * @return boolean
	 */
	public function exists($file)
	{
		try {
			$this->client->getBlob($this->getContainer($file), $this->blobName($file));
			return true;
		} catch (ServiceException $e) {
			\Yii::warning($e->getMessage());
			return false;
		}
	}

	public function createContainer($container, $recursive=true, $permissions=null)
	{
		/**
		 * Source: https://azure.microsoft.com/en-us/documentation/articles/storage-php-how-to-use-blobs/
		 */
		$container = $this->getContainer($container);
		// OPTIONAL: Set public access policy and metadata.
		// Create container options object.
		$createContainerOptions = new CreateContainerOptions();
		$createContainerOptions->setPublicAccess(PublicAccessType::BLOBS_ONLY);

		try {
		    // Create container.
		    $this->client->createContainer($container, $createContainerOptions);
			$this->container = $container;
			return true;
		}
		catch(ServiceException $e){
			\Yii::warning($e->getMessage());
		    // Handle exception based on error codes and messages.
		    // Error codes and messages are here:
		    // http://msdn.microsoft.com/library/azure/dd179439.aspx
		    $this->setErrors([
				'code' => $e->getCode(),
				'message' => $e->getMessage()
			]);
			return false;
		}

	}

	public function removeContainer($container, $recursive=true, $permissions=null)
	{
		/**
		 * Source: https://azure.microsoft.com/en-us/documentation/articles/storage-php-how-to-use-blobs/
		 */
		// OPTIONAL: Set public access policy and metadata.
		// Create container options object.
		$createContainerOptions = new CreateContainerOptions();
		$createContainerOptions->setPublicAccess(PublicAccessType::BLOBS_ONLY);

		try {
		    // Create container.
		    $this->client->deleteContainer($container, $createContainerOptions);
			return true;
		}
		catch(ServiceException $e){
			\Yii::warning($e->getMessage());
		    // Handle exception based on error codes and messages.
		    // Error codes and messages are here:
		    // http://msdn.microsoft.com/library/azure/dd179439.aspx
		    $this->setErrors([
				'code' => $e->getCode(),
				'message' => $e->getMessage()
			]);
			return false;
		}

	}

	public function getUrl($of)
	{
		$key = implode('-', array_filter(['azure', 'bloburl', $of]));
		$listBlobOptions = new ListBlobsOptions;
		$listBlobOptions->setPrefix($this->blobName($of));
		$list = $this->client->listBlobs($this->getContainer($of), $listBlobOptions);
		$ret_val = array_pop($list->getBlobs())->getUrl();
		return $ret_val;
	}
}
?>
