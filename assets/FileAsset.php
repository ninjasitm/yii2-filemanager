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
class FileAsset extends AssetBundle
{
    public $sourcePath = '@vendor/mhdevnet/yii2-filemanager/assets';
    public $css = [
        'css/filemanager.css',
    ];
    public $js = [
        'js/context.js',
        'js/filemanager.js',
        'js/nitm.filemanager.js',
        'js/jquery.filemanager.js',
    ];
    public $depends = [
        'yii\web\JqueryAsset',
        'yii\bootstrap\BootstrapAsset',
    ];
}
