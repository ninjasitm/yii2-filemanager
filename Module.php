<?php

namespace nitm\filemanager;

use yii\helpers\ArrayHelper;
/**
 * Module class.
 */
class Module extends \yii\base\Module implements \yii\base\BootstrapInterface
{
	public $id = "nitm-files";
    public $controllerNamespace = 'nitm\filemanager\controllers';

    public $thumbnails = [[100,100]];

	public $directorySeparator = DIRECTORY_SEPARATOR;

	/**
	 * This is used primarily for local permissions
	 * Permissions in the format:
	 *	[
	 		"type" => [
				"mode" =>
				"group" =>
				"owner" =>
			]
	 *	]
	 */
	public $permissions = [];

	public $engineMap = [
	];

	public $engine = "local";

    public $thumbPath = "thumb/";

    public $url = "/";

	protected $settings = [
	];

	protected $pathMap;

	/**
	 * The custom path map for different file types
	 */

	protected $namespaceMap;

	public $allowedTypes = [
	];

	private $_storageEngines = [
		"local" => "Local",
		"aws" => "AmazonAWS",
		"youtube" => "YouTube",
	];

    public function init()
    {
        parent::init();

		$this->permissions = array_merge($this->defaultPermissions(), $this->permissions);

        // custom initialization code goes here

		/**
		 * Aliases for nitm\widgets module
		 */
		\Yii::setAlias("nitm/filemanager", dirname(__DIR__)."/yii2-filemanager");
    }

	public static function getUrls($id="nitm-files")
	{
		$synonyms = [
			'files' => ['file', 'files'],
			'image' => ['images', 'image']
		];
		$ret_val = [];
		foreach($synonyms as $controller=>$alias)
		{
			$controllers = '('.implode('|', $alias).')';
			$ret_val += [
				"<controller:$controllers>" => $id . "/$controller",
				"<controller:$controllers>/<action>" => $id . "/$controller/<action>",
				"<controller:$controllers>/<action>/<id>" => $id . "/$controller/<action>",
				"<controller:$controllers>/<action:(get|download)>/<id:\d+>/<filename>" => $id . "/$controller/<action>",
				"<controller:$controllers>/<action>/<type>/<id>" => $id . "/$controller/<action>",
				"<controller:$controllers>/<action>/<type>/<remoteType>/<remoteId>" => $id . "/$controller/<action>",
			];
		}
		return $ret_val;
	}

	public function bootstrap($app)
	{
		/**
		 * Setup urls
		 */
        $app->getUrlManager()->addRules($this->getUrls(), false);
	}

	public function getPath($for=null)
	{
		return $this->getPathMap($for);
	}

	public function getType($for=null)
	{
		return ArrayHelper::getValue($this->getTypeMap(), $for, $this->typeMap);
	}

	public function getExtension($for=null)
	{
		return ArrayHelper::getValue(array_flip($this->getExtensionMap()), $for, null);
	}

	public function getBaseType($extension)
	{
		return ArrayHelper::getValue($this->getBaseTypeMap(), $extension, "unknown");
	}

	public function getIsAllowed($extension)
	{
		return in_array($extension, $this->getAllowedTypes());
	}

	public function getAllowedTypes()
	{
		return (count($this->allowedTypes) == 0) ? array_keys($this->getBaseTypeMap()) : array_intersect(array_keys($this->getBaseTypeMap()), $this->allowedTypes);
	}

	protected function resolveEngine($engine=null)
	{
		if(!is_null($engine) && isset($this->engineMap[$engine]))
			return $this->engineMap[$engine];
		else
			return is_null($engine) ? $this->engine : $engine;
	}

	/**
	 * Select the engie to use. This can be either an index map for the engineMap, a tring specifying the engine or null, which will use the current engine
	 **/
	public function getEngine($engine=null)
	{
		$engine = $this->resolveEngine($engine);
		if(isset($this->_storageEngines[$engine]))
			return $this->_storageEngines[$engine];
		else {
			\Yii::warning("Engine: $engine is not supported");
			return $this->_storageEngines['local'];
		}
	}

	public function getEngineClass($engine=null)
	{
		$engine = $this->resolveEngine($engine);
		$class = "\\nitm\\filemanager\helpers\storage\\".$this->getEngine($engine);
		switch(class_exists($class))
		{
			case true:
			return $class;
			break;

			default:
			$class = "\\nitm\\filemanager\helpers\storage\\".$this->getEngine('local');
			\yii::warning("There is no engine for [".$engine."] available. Using local storage engine instead");
			break;
		}
	}

	public function getPermission($types=null, $for=null)
	{
		$types = count($_types = array_map("trim", explode("|", $types))) == 0 ? array_keys($this->permissions) : $_types;
		$for = count($_for = array_map("trim", explode("|", $for))) == 0 ? null : $_for;

		$ret_val = [];
		foreach((array)$types as $idx=>$t)
		{
			if(is_array($for))
				$ret_val[$t] = ArrayHelper::getValue(ArrayHelper::getValue($this->permissions, $t, []), $for[$idx], null);
			else
				$ret_val[$t] = ArrayHelper::getValue($this->permissions, $t, []);
		}
		if(isset($ret_val["mode"]))
			$ret_val["mode"] = $ret_val["mode"][0] === 0 ? $ret_val["mode"] : octdec($ret_val["mode"]);
		return count($ret_val) == 1 ? array_pop($ret_val) : $ret_val;
	}

	public function getWidget($type, $options=[])
	{
		$widgetClass = __NAMESPACE__."\widgets\\".ucfirst(strtolower($type));
		if(class_exists($widgetClass))
			return new $widgetClass($options);
		else
			return " => ";
	}

	public function isSupportedProvider($for, $provider)
	{
		return isset($this->settings[$for]) && isset($this->settings[$for][$provider]);
	}

	private function defaultPermissions()
	{
		return [
			"directory" => [
				"mode" => 0770,
				"owner" => "nobody",
				"group" => "nogroup"
			],
			"file" => [
				"mode" => 0770,
				"owner" => "nobody",
				"group" => "nogroup"
			],
			"image" => [
				"mode" => 0770,
				"owner" => "nobody",
				"group" => "nogroup"
			],
		];
	}

	public function setSettings($settings) {
		$this->settings = $settings;
	}

	private function getExtensionMap()
	{
		return [
			"pdf" => "application/pdf",
			"zip" => "application/zip",
			"pcap" => "application/vnd.tcpdump.pcap",
			"cap" => "application/vnd.tcpdump.pcap",
			"gif" => "image/gif",
			"jpg" => "image/jpeg",
			"png" => "image/png",
			"tiff" => "image/tiff",
			"tif" => "image/tiff",
			"css" => "text/css",
			"html" => "text/html",
			"js" => "text/javascript",
			"txt" => "text/plain",
			"xml" => "text/xml",
		];
	}

	public function getPathMap($index=null)
	{
		if(!isset($this->pathMap))
			$this->setPathMap([]);

		if(is_null($index))
			return $this->pathMap;
		else
			return ArrayHelper::getValue($this->pathMap, $index, null);
	}

	public function setPathMap($paths)
	{
		$this->pathMap = array_merge([
			"image" => "@media/images/",
			"audio" => "@media/audio/",
			"video" => "@media/videos/",
			"text" => "@media/documents/",
			"application" => "@media/applications/",
			"unknown" => "@media/unknown/"
		], (array) $paths);
	}

	public function getNamespaceMap($index=null)
	{
		if(!isset($this->namespaceMap))
			$this->setNamespaceMap([]);

		if(is_null($index))
			return $this->namespaceMap;
		else
			return ArrayHelper::getValue($this->namespaceMap, $index, null);
	}

	public function setNamespaceMap($paths)
	{
		$this->namespaceMap = array_merge([
			"\\nitm\\models\\",
			"\\nitm\\filemanager\\models\\",
			"\\nitm\\widgets\\models\\",
		], (array) $paths);
	}

	private function getTypeMap()
	{
		return array_flip($this->getExtensionMap());
	}

	private function getBaseTypeMap()
	{
		return [
			"txt" => "text",
			"htm" => "text",
			"html" => "text",
			"php" => "text",
			"css" => "text",
			"js" => "text",
			"json" => "text",
			"xml" => "text",
			"pdf" => "text",
			"pcap" => "text",
			"cap" => "text",

			// ms office
			"doc" => "text",
			"rtf" => "text",
			"xls" => "text",
			"ppt" => "text",

			// open office
			"odt" => "text",
			"ods" => "text",

			// images
			"png" => "image",
			"jpe" => "image",
			"jpeg" => "image",
			"jpg" => "image",
			"gif" => "image",
			"bmp" => "image",
			"ico" => "image",
			"tiff" => "image",
			"tif" => "image",
			"svg" => "image",
			"svgz" => "image",

			// adobe
			"psd" => "image",
			"ai" => "image",
			"eps" => "image",
			"ps" => "image",

			// archives
			"swf" => "application",
			"zip" => "application",
			"rar" => "application",
			"exe" => "application",
			"msi" => "application",
			"cab" => "application",

			// audio/video
			"mp3" => "audio",
			"qt" => "video",
			"mov" => "video",
			"flv" => "video",
		];
	}

	public function getModelClass($modelName)
	{
		foreach((array)$this->namespaceMap as $namespace)
		{
			$class = rtrim($namespace, "\\")."\\".\nitm\helpers\ClassHelper::properClassName($modelName);
			if(class_exists($class))
				return $class;
		}
		return null;
	}

	public function setting($setting=null)
	{
		return \yii\helpers\ArrayHelper::getValue($this->settings, $setting, null);
	}
}
