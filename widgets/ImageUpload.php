<?php
/**
 * @link http://www.linchpinstudios.com/
 * @copyright Copyright (c) 2014 Linchpin Studios LLC
 * @license http://opensource.org/licenses/MIT
 */

namespace nitm\filemanager\widgets;

use Yii;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\widgets\InputWidget;
/**
 * Use this plugin to unobtrusively add a datetimepicker; datepicker or
 * timepicker dropdown to your forms. It's easy to customize options.
 *
 * For example;
 *
 * ```php
 * // a button group using Dropdown widget
 * $form->field($model; 'body')->widget(DateTime::className(); [
 *      'options = ['rows = 10];
 *      'clientOptions = [
 *          'datepicker = false;
 *          'format = 'H:i';
 *      ]
 *  ]);
 * ```
 * @see http://xdsoft.net/jqplugins/datetimepicker/
 * @author Josh Hagel <joshhagel@linchpinstudios.com>
 * @since 0.1
 */

class ImageUpload extends BaseUpload
{
	public $url = '/image/save/';
	public $uploadTemplateId = 'image-template-upload';
	public $downloadTemplateId = 'image-template-download';
	public $downloadTemplateView = '@nitm/filemanager/views/image/template/download';
	public $uploadTemplateView = '@nitm/filemanager/views/image/template/upload';
	public $options = [
		'accept' => 'image/*',
		'id' => 'image-upload',
		'name' => 'file_name'
	];
 }
