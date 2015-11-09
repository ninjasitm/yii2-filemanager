<?php

namespace nitm\filemanager\helpers\storage;

use yii\helpers\FileHelper;
use yii\helpers\ArrayHelper;
use nitm\filemanager\models\File;

class Local extends \nitm\filemanager\helpers\Storage implements StorageInterface
{

	/**
	 * Save a file or data to a file
	 * @param string $data
	 * @param string $to
	 * @param array $permissions
	 * @return boolean
	 */
	public static function save($data, $to, $permissions=[])
	{
		$ret_val = false;
		$to = self::getUrl($to);
		$data = $data instanceof File ? $data->url : $data;
		if(!is_dir(dirname($to)))
			static::createContainer(dirname($to), true);

		switch(static::isWriteable($to))
		{
			case true:
			switch(static::exists($data))
			{
				case true:
				$ret_val = copy($data, $to);
				break;

				default:
				$ret_val = file_put_contents($to, $data);
				break;
			}
			static::applyPermissions($to, $permissions);
			break;

			default:
			throw new \yii\base\Exception("The directory: ".dirname($to)." is not writable");
			break;
		}
		return $to;
	}

	/**
	 * Move a file or directory
	 * @param string $from
	 * @param string $to
	 * @param boolean $isUploaded Is this an uploaded file?
	 * @param array $permissions
	 * @return boolean
	 */
	public static function move($from, $to, $isUploaded=false, $permissions=[])
	{
		$to = self::getUrl($to);
		$isFile = static::exists($from);
		if($isFile)
			$from = self::getUrl($from);
		$ret_val = false;

		if(!is_dir(dirname($to)))
			static::createContainer(dirname($to), true);

		switch(static::isWriteable($to))
		{
			case true:
			if($isFile)
				$ret_val =  ($isUploaded === true) ? move_uploaded_file($from, $to) : rename($from, $to);
			else
				$ret_val = file_put_contents($to, $from) === false ? false : true;
			static::applyPermissions($to, $permissions);
			break;

			default:
			throw new \yii\base\Exception("The directory: ".dirname($to)." is not writable");
			break;
		}
		return $to;
	}

	/**
	 * Delete a file matched by path
	 * @param string $container
	 * @param array $options
	 * @return boolean
	 */
	public static function delete($path)
	{
		switch($path)
		{
			case '/':
			case '.':
			return false;
			break;
		}
		return static::exists($path) ? unlink($path) : false;
	}

	/**
	 * Create a container most likely a directory
	 * @param string $container
	 * @param boolean $recursive Recursively create the directories?
	 * @param array $permissions
	 * @return boolean
	 */
	public static function createContainer($container, $recursive=true, $permissions=[])
	{
		$ret_val = false;
		if(FileHelper::createDirectory($container, ArrayHelper::getValue($permissions, 'mode', static::getPermission('directory', 'mode')), $recursive)) {
			static::applyPermissions($container, $permissions, 'directory');
			$ret_val = true;
		}
		return $ret_val;
	}

	/**
	 * Delete a container most likely a directory
	 * @param string $container
	 * @param array $options
	 * @return boolean
	 */
	public static function removeContainer($container, $options=[])
	{
		return FileHelper::removeDirectory($container, $options);
	}

	/**
	 * Get the alias $of
	 * @param string $of
	 * @return string
	 */
	public static function getUrl($of)
	{
		return \Yii::getAlias($of);
	}

	/**
	 * Does $path Exist?
	 * @param string $path
	 * @return boolean
	 */
	public static function exists($path)
	{
		try {
			return is_string($path) && file_exists(static::getUrl($path)) && is_readable(static::getUrl($path));
		} catch (\Exception $e) {
			return false;
		}
	}

	/**
	 * Is $path writable?
	 * @param string $path
	 * @return boolean
	 */
	public static function isWriteable($path)
	{
		return is_writable(dirname(static::getUrl($path)));
	}

	/**
	 * Get the contents of a file
	 * @param string $path
	 * @return string Contents of the file
	 */
	public static function getContents($path)
	{
		$ret_val = null;
		if(static::exists($path))
			$ret_val = file_get_contents($path);
		return $ret_val;
	}

	/**
	 * Apply the permissions
	 * @param $to Apply the permissions to this path
	 * @param array $permissions Permissions ['mode':mode, 'group':group, 'owner': owner]
	 * @param string $type File or Directory permissions?
	 */
	protected static function applyPermissions($to, $permissions=[], $type='file')
	{
		$oldUmask = umask(0);
		try {
			chmod($to, ArrayHelper::getValue($permissions, 'mode', static::getPermission($type, 'mode')));
			chown($to, ArrayHelper::getValue($permissions, 'owner', static::getPermission($type, 'owner')));
			chgrp($to, ArrayHelper::getValue($permissions, 'group', static::getPermission($type, 'group')));
		} catch (\Exception $e) {
		}
		umask($oldUmask);
	}
}
?>
