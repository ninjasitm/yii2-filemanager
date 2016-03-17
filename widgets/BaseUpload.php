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

class BaseUpload extends \dosamigos\fileupload\FileUploadUI
{
	public $model;
	public $attribute = 'file_name';
	public $downloadTemplateView = '@vendor/2amigos/yii2-file-upload-widget/src/views/download';
	public $uploadTemplateView = '@vendor/2amigos/yii2-file-upload-widget/src/views/upload';
	public $galleryTemplateView = '@vendor/2amigos/yii2-file-upload-widget/src/views/gallery';
	public $formView = '@vendor/2amigos/yii2-file-upload-widget/src/views/form';
	public $clientOptions = [
		'limitMultipleFileUploads' => 2,
		'maxFileSize' => 200000000
	];
	// Also; you can specify jQuery-File-Upload events
	// see: https://github.com/blueimp/jQuery-File-Upload/wiki/Options#processing-callback-options
	public $clientEvents = [
		'fileuploaddone' => 'function(e, data) {
		}',
		'fileuploadfail' => 'function(e, data) {
			 $([role="fileUploadMessage"]).html(data.message);
		 }',
		'fileuploadadd' => 'function (e, data) {
			 //Only submit if the form is validated properly
		 }',
		'fileuploadsubmit' => 'function(e, data) {
		 }'
	 ];

	 public function init()
	 {
		 if(!isset($this->model))
		 	throw new \yii\base\Exception("The model needs to be set for the form!!");
		$type = $this->model instanceof \nitm\filemanager\models\File ? $this->model->remote_type : $this->model->isWhat();
		$id = $this->model instanceof \nitm\filemanager\models\File ? $this->model->remote_id : $this->model->getId();
	 	$this->url = rtrim($this->url, '/').'/'.$type.'/'.$id;
		parent::init();
	 }
 }
