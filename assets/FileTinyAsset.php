<?php
/**
 * @link http://www.linchpinstudios.com/
 * @copyright Copyright (c) 2014 Linchpin Studios LLC
 * @license http://opensource.org/licenses/MIT
 */

namespace nitm\filemanager\assets;

use yii\web\AssetBundle;

/**
 * Asset bundle for the Twitter bootstrap css files.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class FileTinyAsset extends AssetBundle
{
    public $sourcePath = '@vendor/nitm/yii2-filemanager/assets';
    public $css = [
        /*'css/filemanager.css',*/
    ];
    public $js = [
        'js/filemanagertiny.js',
        'js/filemanager_plugin.js',
    ];
    public $depends = [
        'dosamigos\tinymce\TinyMceAsset',    ];
}