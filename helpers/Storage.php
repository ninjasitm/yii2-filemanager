<?php

namespace nitm\filemanager\helpers;

use Yii;
use yii\web\UploadedFile;
use yii\helpers\ArrayHelper;

/**
 * This class helps determine where file data is saved.
 * Supports:
 *	local
 *	Amazon S3
 */
class Storage extends \yii\base\Component
{
	public static function getContainers($specifically=null)
	{
		$module = static::getEngine($type);
		return $module->getContainers($specifically);
	}

	/**
	 * Save a file or data to a file
	 * @param string $data
	 * @param string $to
	 * @param array $permissions
	 * @return boolean
	 */
	public static function save($data, $to, $permissions=[], $thumb=false, $path=null, $type=null)
	{
		$module = static::getEngine($type);
		return $module->save($data, $to, static::parsePermissions($permissions), $thumb, $path, $type);
	}

	/**
	 * Move a file or directory
	 * @param string $from
	 * @param string $to
	 * @param boolean $isUploaded Is this an uploaded file?
	 * @param array $permissions
	 * @return boolean
	 */
	public static function move($from, $to, $isUploaded=false, $thumb=false, $fileType=null, $type=null)
	{
		$module = static::getEngine($type);
		return $module->move($from, $to, $isUploaded, $thumb, $fileType);
	}

	/**
	 * Get the alias $of
	 * @param string $of
	 * @return string
	 */
	public static function getUrl($of, $type=null)
	{
		$module = static::getEngine($type);
		return $module->getUrl($of);
	}

	/**
	 * Does $path Exist?
	 * @param string $path
	 * @return boolean
	 */
	public static function exists($path, $type=null)
	{
		$module = static::getEngine($type);
		return $module->exists($path);
	}

	/**
	 * Delete files
	 * @param mixed $of
	 * @return boolean
	 */
	public static function delete($path, $type=null)
	{
		$module = static::getEngine($type);
		return $module->delete($path);
	}

	/**
	 * Is $path writable?
	 * @param string $path
	 * @return boolean
	 */
	public static function isWriteable($path, $type=null)
	{
		$module = static::getEngine($type);
		return $module->isWritable($path);
	}

	/**
	 * Get the contents of a file
	 * @param string $path
	 * @param string $engine The stroage engine to use
	 * @return string Contents of the file
	 */
	public static function getContents($path, $engine=null)
	{
		$module = \Yii::$app->getModule('nitm-files')->getEngineClass($engine);
		return $module->getContents($path);
	}

	/**
	 * Create a container/directory
	 * @param string $container
	 * @param boolean $resursive
	 */
	public static function createContainer($container, $recursive=true, $permissions=[], $type=null)
	{
		$module = static::getEngine($type);
		return $module->createContainer($container, $recursive, static::parsePermissions($permissions));
	}

	/**
	 * Create a container/directory
	 * @param string $container
	 * @param boolean $resursive
	 */
	public static function containerExists($container, $type=null)
	{
		$module = static::getEngine($type);
		return $module->containerExists($container);
	}

	/**
	 * Delete a container/directory
	 * @param string $container
	 * @param boolean $resursive
	 */
	public static function removeContainer($container, $options=[], $type=null)
	{
		$module = static::getEngine($type);
		return $module->removeContainer($container, $options, $type);
	}

	/**
	 * Convert the permissions
	 * @param array $permissions Permissions ['mode':mode, 'group':group, 'owner': owner]
	 * @return octal permissions
	 */
	protected static function parsePermissions($permissions=[])
	{
		if(isset($permissions['mode']))
			$permissions['mode'] = is_null($permissions['mode']) ? null : octdec((string)$permissions['mode']);
		return $permissions;
	}

	/**
	 * Apply the permissions
	 * @param $to Apply the permissions to this path
	 * @param array $permissions Permissions ['mode':mode, 'group':group, 'owner': owner]
	 * @param string $type File or Directory permissions?
	 */
	protected static function applyPermissions($to, $permissions=[], $type='file', $fileType=null)
	{
		$module = static::getEngine($type);
		return $module->applyPermissions($to, $permissions, $type);
	}

	/**
	 * Get the permissions
	 * @parm string $type 'directory'|'file'
	 * @parm string $for 'mode|owner|group'
	 * @retrun array permissions
	 */
	protected static function getPermission($type=null, $for=null)
	{
		return \Yii::$app->getModule('nitm-files')->getPermission($type, $for);
	}

	private function getEngine($type)
	{
		return \Yii::$app->getModule('nitm-files')->getEngine($type);
	}
}
