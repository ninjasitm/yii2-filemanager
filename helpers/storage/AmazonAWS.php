<?php

namespace nitm\filemanager\helpers\storage;

use nitm\filemanager\models\File;

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
    
    public function initClient()
	{
		if!isset(static::$_client))
		{
			static::$_config = \Yii::$app->get('nitm-files')->aws;
		
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
					'key'    => static::$_config['key'],
					'secret' => static::$_config['secret'],
				];
				static::$_client = S3Client::factory($config);
			}
		}
    }
	
	public static function containers($specifically=null)
	{
		//Need to get aWs containers here
		//return \Yii::$app->get('nitm-files')->getPath($specifically);
	}
    
    protected function save($file, $name = null, $thumb = false, $path = null)
	{
        static::initClient();
		
        if(is_null($path)){
            $path = \Yii::$app->get('nitm-files')->path[$file->url];
        }
        
        if(is_null($name)){
            $name = basename($file->url);
        }
        
        if($thumb){
            $path = $path.'thumbnails/';
        }
        
        static::$_client->get('S3');
        
        static::$_client->putObject([
            'Key' => $path.$name,
            'Bucket' => static::$_config['bucket'],
            'SourceFile' => $file->url,
            'ContentType' => $file->type,
        ]);
        
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
        
    }
	
	public static function move($from, $to, $isUploaded=false)
	{
		$to = self::getUrl($to);
		$from = self::getUrl($from);
		$ret_val = false;
		switch(is_writable(dirname($to)))
		{
			case true:
			$ret_val =  ($isUploaded === true) ? move_uploaded_file($from, $to) : rename($from, $to);
			$oldUmask = umask(0);
			chmod($to, self::FILE_MODE);
			umask($oldUmask);
			break;
			
			default:
			throw new \yii\base\Exception("The directory: ".dirname($to)." is not writable");
			break;
		}
		return $ret_val;
	}
	
	public static function createContainer($container, $recursive=true)
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