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

class BaseUpload extends BaseWidget
{
	public $model;
	public $url;
	public $attribute = 'file_name';
	public $enableUrlUpload = true;
	public $widgetOptions = [];
	public $formView = '@nitm/filemanager/views/upload/form';

	 public function init()
	 {
		 if(!isset($this->model))
		 	throw new \yii\base\Exception("The model needs to be set for the form!!");
		$type = $this->model instanceof \nitm\filemanager\models\File ? $this->model->remote_type : $this->model->isWhat();
		$id = $this->model instanceof \nitm\filemanager\models\File ? $this->model->remote_id : $this->model->getId();
		$urlKey = 'image/save/'.$type.'/'.$id;
		if(strpos($this->url, $urlKey) === false)
	 		$this->url = rtrim($this->url, '/').'/'.$urlKey;
		\nitm\filemanager\assets\FileAsset::register($this->view);
		$this->widgetOptions = array_merge($this->defaultWidgetOptions, $this->widgetOptions);
		parent::init();
	 }

	 public function run()
	 {
		 return $this->render($this->formView, [
			 'model' => $this->model,
			 'widget' => $this,
			 'widgetOptions' => $this->widgetOptions
		 ]);
	 }

	 protected function getDefaultWidgetOptions()
	 {
		 return [
			'model' => $this->model,
			'url' => $this->url,
			'attribute' => $this->attribute,
			"downloadTemplateView" => '@vendor/2amigos/yii2-file-upload-widget/src/views/download',
			"uploadTemplateView" => '@vendor/2amigos/yii2-file-upload-widget/src/views/upload',
			"galleryTemplateView" => '@vendor/2amigos/yii2-file-upload-widget/src/views/gallery',
			'clientOptions' => [
				'limitMultipleFileUploads' => 2,
				'maxFileSize' => 200000000
			],
			'clientEvents' => [
				'fileuploadfail' => 'function(e, data) {
					 $([role="fileUploadMessage"]).html(data.message);
				 }',
			 ]
		 ];
	 }
 }
