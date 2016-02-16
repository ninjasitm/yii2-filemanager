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
	public function getContainers();

	/**
	 * Function to save data
	 * @param string $data
	 * @param string $to
	 */
	public function save($data, $to, $permissions=null);

	/**
	 * Function to move data
	 * @param string $from
	 * @param string $to
	 * @param boolean $isUploaded
	 */
	public function move($from, $to, $isUploaded=false);

	/**
	 * Get the real location of a file
	 * @param mixed $of
	 */
	public function getUrl($of);

	/**
	 * Delete a file based on $id
	 * @param mixed $id
	 */
	public function delete($id);

	/**
	 * Does the item pointed to by $id exist?
	 * @param mixed $id
	 */
	public function exists($id);

	public function createContainer($container, $recursive, $permissions=null);

	public function containerExists($container);

	public function removeContainer($container, $options=[]);
}

?>
