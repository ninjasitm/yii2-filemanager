<?php

namespace nitm\filemanager;

/**
 * Module class.
 */
class Module extends \yii\base\Module
{
    
    public $controllerNamespace = 'nitm\filemanager\controllers';
    
    public $thumbnails = [[100,100]];
    
    public $pathMap = [
		'image' => '@media/images/',
		'audio' => '@media/audio/',
		'video' => '@media/video/',
		'document' => '@media/document/'
	];
    
    public $thumbPath = 'thumb/';
    
    public $url = '/';
    
    public $aws = [
        'enable' => false,
        'key' => '',
        'secret' => '',
    	'bucket' => '',
    ];

    private $extensionMap = [
        'application/pdf'   => '.pdf',
        'application/zip'   => '.zip',
        'image/gif'         => '.gif',
        'image/jpeg'        => '.jpg',
        'image/png'         => '.png',
        'text/css'          => '.css',
        'text/html'         => '.html',
        'text/javascript'   => '.js',
        'text/plain'        => '.txt',
        'text/xml'          => '.xml',
    ];

    private $typeMap = [
        'pdf'   => 'application/pdf',
        'zip'   => 'application/zip',
        'gif'   => 'image/gif',
        'jpg'   => 'image/jpeg',
        'jpeg'  => 'image/jpeg',
        'png'   => 'image/png',
        'css'   => 'text/css',
        'html'  => 'text/html',
        'js'    => 'text/javascript',
        'txt'   => 'text/plain',
        'xml'   => 'text/xml',
    ];
    
    public function init()
    {
        parent::init();

        // custom initialization code goes here
		
		/**
		 * Aliases for nitm\widgets module
		 */
		\Yii::setAlias('nitm/filemanager', dirname(__DIR__)."/yii2-filemanager");
    }
	
	public function getType($for=null) {
		return ArrayHelper::getValue($this->typeMap, $for, $this->typeMap);
	}
	
	public function getExtension($for=null) {
		return ArrayHelper::getValue($this->extensionMap, $for, null);
	}
}

