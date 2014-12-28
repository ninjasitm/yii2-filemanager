<?php

namespace nitm\filemanager\helpers\storage;

/**
 * This is the interface for the stroage controllers. 
 */

interface StorageInterface 
{	
	/**
	 * Return the storage container locations by type:
	 * [
	 *		"image" => "@media/image"
	 *		"video" => "@media/video"
	 *		...
	 * ]
	 * @return mixed|string
	 */
	public static function containers();
	
	/**
	 * Function to save data
	 * @param string $data
	 * @param string $to
	 */
	public static function save($data, $to, $permissions=null);
	
	/**
	 * Function to move data
	 * @param string $from
	 * @param string $to
	 * @param boolean $isUploaded
	 */
	public static function move($from, $to, $isUploaded=false);
	
	/**
	 * Get the real location of a file
	 * @param mixed $of
	 */
	public static function getUrl($of);
	
	/**
	 * Delete a file based on $id
	 * @param mixed $id
	 */
	public static function delete($id);
	
	public static function createContainer($container, $recursive, $permissions=null);
	
	public static function removeContainer($container, $options=[]);
}

?>