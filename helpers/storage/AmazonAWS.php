<?php

namespace nitm\filemanager\helpers\storage;

use nitm\filemanager\models\File;
use nitm\helpers\Cache;
use nitm\helpers\ArrayHelper;

class AmazonAWS extends BaseStorage implements StorageInterface
{
	protected $clientClass = "\Aws\S3\S3Client";

    public function initClient()
	{
		if(!isset($this->client))
		{
			$this->_config = \Yii::$app->getModule('nitm-files')->setting('aws.s3');

			if($this->_config)
			{
				if($this->_config['key'] == ''){
					throw new InvalidConfigException('Key cannot be empty!');
				}
				if($this->_config['secret'] == ''){
					throw new InvalidConfigException('Secret cannot be empty!');
				}
				if($this->_config['bucket'] == ''){
					throw new InvalidConfigException('Bucket cannot be empty!');
				}

				$config = [
					'credentials' => new \Aws\Common\Credentials\Credentials($this->_config['key'], $this->_config['secret'])
				];
				$this->client = \Aws\S3\S3Client::factory($config);
			}
		}
    }

	public function getContainers($specifically=null)
	{
		$key = implode('-', array_filter(['s3', 'buckets', $specifically]));
		if(!isset($this->_containers))
		{
			$this->_containers = [];
			foreach($this->client->getIterator('ListObjects', ['Bucket' => $this->_config['bucket']]) as $bucket)
				$this->_containers[$bucket['key']] = $bucket['key'];
		}
		return $this->_containers;
	}

	protected function getPutOptions($file, $type)
	{
		//If this is a resource, most likely a stream of data
		if(is_string($file)) {
			return $options = [
				'ContentType' => $type,
				'Body' => $file
			];
		}
		else
			return $options = [
				'SourceFile' => $file->url,
				'ContentType' => $file->type,
			];
	}

    public function save($file, $name = null, $thumb=false, $path=null, $type=null)
	{
		list($container, $path, $fullPath, $name, $filePath) = parent::beforeAction($file, $name, $thumb, $path, $type);

        $this->client->get('S3');

        $url =  $this->client->putObject(array_merge([
            'Key' => $fullPath,
            'Bucket' => $this->_config['bucket'],
			'ACL' => 'public-read',
        ], $this->getPutOptions($file, $type)))->get('ObjectURL');

		return $url ? $url : false;

    }

    public function delete($files)
	{
		$this->client->get('S3');
		foreach((array)$files as $file)
		{
			$this->client->deleteObjects([
				'Bucket' => $this->_config['bucket'],
				'key' => $file->url,
			]);
		}
		return true;
    }

	public function move($from, $to, $isUploaded=false, $thumb=false, $type=null)
	{
		try {
			if(file_exists($this->getUrl($from)))
				$from = new \nitm\filemanager\models\File([
					'url' => $from,
					'type' => (is_null($type) ? ArrayHelper::getValue(getImageSize($from), 'mime', 'binary/octet-stream') : $type)
				]);
		} catch(\Exception $e) {}

		$ret_val = $this->save($from, basename($to), $thumb, dirname($to), $type);

		if($ret_val) {
			if(is_object($from))
				unlink($from->url);
			else if(filter_var($from, FILTER_VALIDATE_URL))
				unlink($from);
			else
				unset($from);
		}

		return $ret_val;
	}

	public function createContainer($container, $recursive=true, $permissions=null)
	{
		/*$oldUmask = umask(0);
		mkdir($container, self::DIR_MODE, $recursive);
		chmod($container, self::DIR_MODE);
		umask($oldUmask);*/
	}
}
?>
