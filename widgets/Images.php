<?php
/**
 * @copyright Copyright &copy; Malcolm Paul, Nitm Inc, 2014
 * @version 1.0.0
 */

namespace nitm\filemanager\widgets;

use Yii;
use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\widgets\ListView;
use kartik\widgets\FileInput;
use nitm\filemanager\models\Image;
use nitm\filemanager\helpers\Storage;
use nitm\helpers\Helper;
use nitm\helpers\Icon;

/**
 * Extends the kartik\widgets\FileInput widget.
 *
 * @author Malcolm Paul <lefteyecc@ninjasitm.com>
 * @since 1.0
 */
class Images extends BaseWidget
{

    /**
     * @var array the HTML attributes for the extra image wrapper.
     */
	public $infoOptions = [
		'class' => 'col-md-4 col-lg-4',
		'role' => 'infoContainer'
	];
	
	public $options = [
		'class' => 'col-md-8 col-lg-8',
		'role' => 'imagesContainer',
		'id' => 'images'
	];
	
	public $pluginOptions = [
		"pluginOptions" => [
			'previewFileType' => 'image',
			'showCaption' => false,
			'showPreview' => true,
			'showUpload' => true,
			'uploadClass' => 'btn btn-success image-upload-button',
			'removeClass' => 'btn btn-xs',
			'browseClass' => 'btn btn-xs'
		],
		'options' => [
			'accept' => 'image/*',
			'type' => 'file'
		]
	];
	
	public $pluginExtraOptions = [
		"pluginOptions" => [
			'previewFileType' => 'image',
			'showCaption' => false,
			'showPreview' => true,
			'showUpload' => true,
			'uploadClass' => 'btn btn-xs btn-success image-upload-button',
			'removeClass' => 'btn btn-xs',
			'browseClass' => 'btn btn-xs',
			'previewClass' => 'file-preview-sm'
		],
		'options' => [
			'accept' => 'image/*',
			'type' => 'file'
		]
	];
	
	protected $imageModel;
	
	/**
	 * The actions that are supported
	 */
	private $_actions = [
		'delete' => [
			'tag' => 'span',
			'tagOptions' => [
				'text' => 'Remove',
				'class' => 'small glyphicon glyphicon-ban-circle',
			],
			'action' => '/image/delete',
			'options' => [
				'data-pjax' => 0,
				'data-method' => 'post',
				'class' => 'text-danger',
				'role' => 'deleteImage',
				'id' => 'delete',
				'title' => 'Delete this Image'
			]
		],
		'default' => [
			'tag' => 'span',
			'tagOptions' => [
				'text' => 'Make Default',
				'class' => 'small glyphicon glyphicon-thumbs-up',
			],
			'action' => '/image/default',
			'options' => [
				'data-pjax' => 0,
				'class' => 'text-success',
				'role' => 'toggleDefaultImage',
				'id' => 'toggle-default',
				'title' => 'Set this image as default'
			]
		],
	];

    /**
     * Runs the widget
     */
    public function run()
    {
		$images = $this->getImages();
		$initScript = \Yii::$app->request->isAjax ? Html::script("\$nitm.onModuleLoad('nitm-file-manager:images', function (module) {module.init();});", ['type' => 'text/javascript']) : '';
		$info = $this->getInfoPane();
		return $images.$initScript.$info;
    }
	
	protected function getAssets() 
	{
		return [
			\nitm\filemanager\assets\ImagesAsset::className()
		];
	}
	
	protected function getInfoPane()
	{
		return Html::tag('div', Html::tag('div', $this->getUploadButton()), $this->infoOptions);
	}
	
	protected function getUploadButton()
	{
		$text = \yii\helpers\ArrayHelper::remove($this->buttonOptions, 'text', 'Add Images');
		$options = array_replace_recursive([
			'size' => 'large',
			'toggleButton' => [
				'tag' => 'a',
				'label' => Icon::forAction('plus')." ".$text, 
				'href' => \Yii::$app->urlManager->createUrl(['/image/form/create/'.$this->model->isWhat().'/'.$this->model->getId(), '__format' => 'modal']),
				'title' => \Yii::t('yii', $text),
				'role' => 'dynamicAction createAction disabledOnClose',
				'class' => 'btn btn-primary'
			],
		], (array)$this->buttonOptions);
		
		return \nitm\widgets\modal\Modal::widget($options);
	}
	
	/**
	 * Get the preview image
	 * @param nitm\filemanager\models\Image $image
	 * @param boolean $default Is this the default image
	 * @param boolean $placeholder Is this a placeholder
	 * @return html string
	 */
	protected function getThumbnail($model=null, $default=false, $placeholder=false)
	{		
		return \Yii::$app->getView()->render("/image/thumbnail", [
			'model' => $model,
			'wrapperOptions' => $thumbnailOptions
		]);
	}
	
	protected function getImages()
	{
		//Use smaller preview images for extra images
		$pluginOptions = $this->pluginOptions;
		$pluginOptions['pluginOptions']['previewClass'] = 'file-preview-sm';
		$this->options['data-id'] = $this->model->getId();
		return ListView::widget([
			'options' => $this->options,
			'dataProvider' => new \yii\data\ArrayDataProvider([
				'allModels' => $this->model->images
			]),
			'itemOptions' => [
				'tag' => false
			],
			'itemView' => function ($model, $key, $index, $widget) {
				return $this->getImage($model);
			}
		]);
	}
	
    /**
     * @var array the HTML attributes for the defualt image wrapper.
     */
    protected function defaultOptions()
	{
		return [
			'class' => 'text-center col-md-3 col-lg-3 col-sm-6',
			'role' => 'imageContainer'
		];
	}
	
	protected function getImage($image)
	{
		$id = $this->getInputId($image);
		$this->pluginExtraOptions['attribute'] = 'images['.$id.']';
		$this->pluginExtraOptions['pluginOptions']['uploadUrl'] = '/image/save/'.$this->model->isWhat().'/'.$this->model->getId();
		$this->pluginExtraOptions['model'] = $this->model;
		$this->pluginExtraOptions['options']['id'] = $id;
        $this->defaultOptions['class'] .= ' image';
		$this->defaultOptions['id'] = $id;
		$this->defaultOptions['role'] .= ' '.($image->isDefault() ? 'defaultImage' : 'extraImage');
		return \Yii::$app->getView()->render("@nitm/filemanager/views/image/view", [
			'model' => $image,
			"actions" => $this->getActions(),
			'wrapperOptions' => $this->defaultOptions,
			'pluginOptions' => $this->pluginExtraOptions
		]);
	}
	
	protected function getProgressHtml()
	{
		return Html::tag('div',
			Html::tag('div', 
				Html::tag('div', '', ['id' => 'percentage']), 
				['id' => 'bar', 'class' => 'clear']
			).
			Html::tag('div', '', ['id' => 'message', 'class' => 'clear']),
			[
				'class' => 'progress well',
				'id' => 'progress',
				'style' => 'display:none'
			]
		);
	}
}
