<?php

namespace nitm\filemanager\helpers\storage;

use yii\helpers\ArrayHelper;
use nitm\filemanager\models\File;
use nitm\helpers\Cache;

class AmazonAWS extends \nitm\filemanager\helpers\Storage implements StorageInterface 
{
	/**
	 * Constants
	 */
	const DIR_MODE = 0777;
	const FILE_MODE = 0777;
	
	private static $_client;
	private static $_config;
    
    public function init()
	{
        parent::init();
		static::initClient();
    }
    
    public static function initClient()
	{
		if(!isset(static::$_client))
		{
			static::$_config = \Yii::$app->getModule('nitm-files')->setting('aws.s3');
		
			if(static::$_config)
			{
				if(static::$_config['key'] == ''){
					throw new InvalidConfigException('Key cannot be empty!');
				}
				if(static::$_config['secret'] == ''){
					throw new InvalidConfigException('Secret cannot be empty!');
				}
				if(static::$_config['bucket'] == ''){
					throw new InvalidConfigException('Bucket cannot be empty!');
				}
				
				$config = [
					'credentials' => new \Aws\Common\Credentials\Credentials(static::$_config['key'], static::$_config['secret'])
				];
				static::$_client = \Aws\S3\S3Client::factory($config);
			}
		}
    }
	
	public static function containers($specifically=null)
	{
        static::initClient();
		//Need to get aWs containers here
		//return \Yii::$app->get('nitm-files')->getPath($specifically);
		$ret_val = [];
		$key = implode('-', array_filter(['s3', 'buckets', $specifically]));
		if(!Cache::exists($key))
		{
			foreach(static::$_client->getIterator('ListObjects', ['Bucket' => static::$_config['bucket']]) as $bucket)
			{
				$ret_val[$bucket['key']] = $bucket['key'];
			}
			Cache::cache()->set($key, $ret_val, 300);
		}
		else
			$ret_val = Cache::cache()->get($key);
		return $ret_val;
	}
	
	protected static function getPutOptions($file, $type)
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
    
    public static function save($file, $name = null, $thumb=false, $path=null, $type=null)
	{
        static::initClient();
		
        if(is_null($path)){
            $path = \Yii::$app->getModule('nitm-files')->path[$file->url];
        }
        
        if(is_null($name)){
            $name = basename($file->url);
        }
        
        if($thumb){
            $path = $path.'thumbs/';
        }
        
        static::$_client->get('S3');
				
        $url =  static::$_client->putObject(array_merge([
            'Key' => $path.$name,
            'Bucket' => static::$_config['bucket'],
			'ACL' => 'public-read',
        ], static::getPutOptions($file, $type)))->get('ObjectURL');
		
		return $url ? $url : false;
        
    }
	
    public static function delete($files)
	{
		static::$_client->get('S3');
		foreach((array)$files as $file)
		{
			static::$_client->deleteObjects([
				'Bucket' => static::$_config['bucket'],
				'key' => $file->url,
			]);
		}
		return true;
    }
	
	public static function move($from, $to, $isUploaded=false, $thumb=false, $type=null)
	{
		try {
			if(file_exists(static::getUrl($from)))
				$from = new \nitm\filemanager\models\File([
					'url' => $from,
					'type' => (is_null($type) ? ArrayHelper::getValue(getImageSize($from), 'mime', 'binary/octet-stream') : $type)
				]);
		} catch(\Exception $e) {}
		
		$ret_val = static::save($from, basename($to), $thumb, dirname($to), $type);
				
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
	
	public static function createContainer($container, $recursive=true, $permissions=null)
	{
		/*$oldUmask = umask(0);
		mkdir($container, self::DIR_MODE, $recursive);
		chmod($container, self::DIR_MODE);
		umask($oldUmask);*/
	}
	
	public static function getUrl($of)
	{
		return \Yii::getAlias($of);
	}
}
?>