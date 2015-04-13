<?php
/**
 * @copyright Copyright &copy; Malcolm Paul, Nitm Inc, 2014
 * @version 1.0.0
 */

namespace nitm\filemanager\widgets;

use Yii;
use yii\helpers\Html;
use yii\helpers\ArrayHelper;
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
class Images extends \yii\base\Widget
{	
	public $model;
	public $withForm;
	
    /**
     * @var Number of extra images supported.
     */
    public $extraImages = 6;
	
    /**
     * @var string the title for the alert. If set to empty or null, will not be
     * displayed.
     */
    public $title = '';

    /**
     * @var array the HTML attributes for the title. The following options are additionally recognized:
     * - tag: the tag to display the title. Defaults to 'span'.
     */
    public $titleOptions = ['class' => 'kv-alert-title'];

    /**
     * @var array the HTML attributes for the defualt image wrapper.
     */
    public $defaultOptions = ['class' => 'well text-center'];

    /**
     * @var array the HTML attributes for the defualt image.
     */
    public $defaultWrapperOptions = ['class' => 'col-md-4 col-lg-4 images-default', 'id' => 'default-image', 'role' => 'imageContainer'];

    /**
     * @var array the HTML attributes for the extra image.
     */
    public $extraOptions = ['class' => 'well text-center col-md-4 col-lg-4 images-extra', 'role' => 'imageContainer'];

    /**
     * @var array the HTML attributes for the extra image wrapper.
     */
    public $extraWrapperOptions = ['class' => 'col-md-8 col-lg-8'];
	
	public $options = [
		'class' => 'well col-md-12 col-lg-12 clearfix',
		'role' => 'imagesContainer'
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
	
	public function init()
	{
		$this->registerAssets();
	}

    /**
     * Runs the widget
     */
    public function run()
    {
		$defaultImage = $this->getDefaultImage();
		$extraImages = $this->getExtraImages();
		$initScript = \Yii::$app->request->isAjax ? Html::script("\$nitm.onModuleLoad('nitm-file-manager:images', function (module) {module.init();});", ['type' => 'text/javascript']) : '';
		echo Html::tag("div", $defaultImage.$extraImages.$initScript, $this->options);
    }

    /**
     * Gets the title section
     *
     * @return string
     */
    protected function getTitle()
    {
        $icon = '';
        $title = '';
        $separator = '';
        if (!empty($this->icon) && $this->iconType == 'image') {
            $icon = Html::img($this->icon, $this->iconOptions);
        } elseif (!empty($this->icon)) {
            $this->iconOptions['class'] = $this->icon . ' ' . (empty($this->iconOptions['class']) ? 'kv-alert-title' : $this->iconOptions['class']);
            $icon = Html::tag('span', '', $this->iconOptions) . ' ';
        }
        if (!empty($this->title)) {
            $tag = ArrayHelper::remove($this->titleOptions, 'tag', 'span');
            $title = Html::tag($tag, $this->title, $this->titleOptions);
            if ($this->showSeparator) {
                $separator = '<hr class="kv-alert-separator">' . "\n";
            }
        }
        return $icon . $title . $separator;
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

    /**
     * Register client assets
     */
    public function registerAssets()
    {
        $view = $this->getView();
        \nitm\filemanager\assets\ImagesAsset::register($view);
    }
	
	public function getActions()
	{
		return $this->_actions;
	}
	
	public function getExtraImages()
	{
		//Use smaller preview images for extra images
		$pluginOptions = $this->pluginOptions;
		$pluginOptions['pluginOptions']['previewClass'] = 'file-preview-sm';
		$extraImages = '';
		if($this->extraImages > 0)
		{
			for($i=0;$i<$this->extraImages;$i++)
			{
				switch(isset($this->model->images[$i]))
				{
					case true:
					$img = $this->model->images[$i];
					break;
					
					default:
					$img = new Image();
					break;
				}
				$extraImage[] = $this->getExtraImage($img, $i);
			}
			$extraImages = Html::tag("div",
				implode('', $extraImage),
				$this->extraWrapperOptions
			);
		}
		return $extraImages;
	}
	
	/**
	 * This is used by the controller to render an image
	 */
	public function getDefaultImageRenderOptions($image)
	{
		$this->pluginOptions['attribute'] = 'images[default]';
		$this->pluginOptions['pluginOptions']['uploadUrl'] = '/image/save/'.$this->model->isWhat().'/'.$this->model->getId();
		$this->pluginOptions['model'] = $this->model;
		return [
			"view" => "@nitm/filemanager/views/image/view", 
			"options" => [
				'model' => !$this->model->icon ? new Image() : $this->model->icon,
				'wrapperOptions' => $this->defaultWrapperOptions,
				"actions" => $this->getActions(),
				'pluginOptions' => $this->pluginOptions
			]
		];
	}
	
	/**
	 * This is used by the controller to render an image
	 */
	public function getExtraImageRenderOptions($image, $counter)
	{	
		$this->pluginExtraOptions['attribute'] = 'images[extra]['.$counter.']';
		$this->pluginExtraOptions['pluginOptions']['uploadUrl'] = '/image/save/'.$this->model->isWhat().'/'.$this->model->getId();
		$this->pluginExtraOptions['model'] = $this->model;
		return  [
			"view" => "@nitm/filemanager/views/image/view", 
			"options" => [
				'model' => $image,
				'wrapperOptions' => $this->extraOptions,
				"actions" => $this->getActions(),
				'pluginOptions' => $this->pluginExtraOptions
			]
		];
	}
	
	public function getDefaultImage()
	{
		$model = $this->model->icon instanceof Image ? $this->model->icon : new Image();
		$this->pluginOptions['attribute'] = 'images[default]';
		$this->pluginOptions['pluginOptions']['uploadUrl'] = '/image/save/'.$this->model->isWhat().'/'.$this->model->getId();
		$this->pluginOptions['model'] = $this->model;
		$this->pluginOptions['options']['id'] = $this->getInputId($model);
		$this->defaultWrapperOptions['id'] = "default-image";
		return \Yii::$app->getView()->render("@nitm/filemanager/views/image/view", [
			'model' => $model,
			'wrapperOptions' => $this->defaultWrapperOptions,
			"actions" => $this->getActions(),
			'pluginOptions' => $this->pluginOptions
		]);
	}
	
	public function getExtraImage($image, $counter)
	{	
		$model = $image instanceof Image ? $image : new Image();
		$this->pluginExtraOptions['attribute'] = 'images[extra]['.$counter.']';
		$this->pluginExtraOptions['pluginOptions']['uploadUrl'] = '/image/save/'.$this->model->isWhat().'/'.$this->model->getId();
		$this->pluginExtraOptions['model'] = $this->model;
		$this->pluginExtraOptions['options']['id'] = $this->getInputId($model);
		$this->extraOptions['id'] = "extra-image".$model->getId();
		return  \Yii::$app->getView()->render("@nitm/filemanager/views/image/view", [
			'model' => $model,
			'wrapperOptions' => $this->extraOptions,
			"actions" => $this->getActions(),
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
	
	protected function getInputId($model)
	{
		return $this->model->isWhat()."-image-".(($model->getId() == 0) ? uniqid() : $model->getId());
	}
}
