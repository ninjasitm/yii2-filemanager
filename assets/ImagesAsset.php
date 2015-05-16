<?php
/**
 * @link http://www.pickledup.com/
 * @copyright Copyright (c) 2014 PickledUp
 */

namespace nitm\filemanager\assets;

use yii\web\AssetBundle;

/**
 * @author Malcolm Paul <lefteyecc@nitm.com>
 */
class ImagesAsset extends AssetBundle
{
	public $sourcePath = "@nitm/filemanager/assets/";
	public $css = [
		'css/images.css'
	];
	public $js = [
		'js/images.js'
	];	
	public $depends = [
		'nitm\assets\AppAsset',
		'kartik\file\FileInputAsset',
	];
}