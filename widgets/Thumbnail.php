<?php
/**
 * @copyright Copyright &copy; Malcolm Paul, Nitm Inc, 2014
 * @version 1.0.0
 */

namespace nitm\filemanager\widgets;

use Yii;
use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use nitm\filemanager\models\Image;
use nitm\filemanager\helpers\Storage;
use nitm\helpers\Helper;
use kartik\widgets\FileInput;
use kartik\icons\Icon;

/**
 * Extends the kartik\widgets\FileInput widget.
 *
 * @author Malcolm Paul <lefteyecc@ninjasitm.com>
 * @since 1.0
 */
class Thumbnail extends \yii\base\Widget
{
	public $htmlIcon;
	public $size = 'default';
	public $model;
	public $url;

    /**
     * @var string the title for the thumbnail. If set to empty or null, will not be
     * displayed.
     */
    public $title;

    /**
     * @var array the HTML attributes for the title. The following options are additionally recognized:
     * - tag: the tag to display the title. Defaults to 'span'.
     */
    public $imageOptions = [];

	public $options = [
		'class' => 'thumbnail',
	];

	public function init()
	{
		parent::init();
		if(!$this->model && $this->url) {
			$this->model = new Image([
				'url' => $this->url
			]);
		}
	}

	public function run()
	{
		$this->model = $this->model instanceof Image ? $this->model : new Image();
		$this->options['class'] .= ' '.$this->getSize($this->size);
		$url = $this->model->metadata($this->getSize().'.value');
		switch(true)
		{
			case $url && $this->model->getIsNewRecord() && !isset($this->htmlIcon):
			$thumbnail = Html::tag('div', Html::img($url, $this->imageOptions), $this->options);
			break;

			case !$url:
			case $this->model->getIsNewRecord() && isset($this->htmlIcon):
			unset($this->options['class']);
			$thumbnail = Html::tag('span', Image::getHtmlIcon($this->htmlIcon, $this->iconSize), $this->options);
			break;

			default:
			$thumbnail = Html::tag('div', Html::img($url, $this->imageOptions), $this->options);
			break;
		}
		if(isset($this->title))
			$thumbnail .= $this->title;
		return $thumbnail;
	}

	protected function getSize()
	{
		switch($this->size)
		{
			case 'small':
			case 'tiny':
			$size = 'small';
			break;

			case 'medium':
			case 'normal':
			$size = 'medium';
			break;

			case 'large':
			$size = 'large';
			break;

			default:
			$size = 'default';
			break;
		}
		return $size;
	}

	protected function getIconSize()
	{
		switch($this->size)
		{
			case 'small':
			case 'tiny':
			$size = '1x';
			break;

			case 'medium':
			case 'normal':
			$size = '2x';
			break;

			case 'large':
			$size = '3x';
			break;

			default:
			$size = '1x';
			break;
		}
		return $size;
	}

	/**
	 * Sizes supported
	 */
	protected function getClass($size=null)
	{
		$sizes = [
			'tiny' => 'thumbnail-xs',
			'small' => 'thumbnail-sm',
			'default' => 'thumbnail',
			'large' => 'thumbnail-lg',
		];
		return \nitm\helpers\ArrayHelper::getValue($sizes, $size, $sizes['default']);
	}

	/**
	 * The actions that are supported
	 */
	protected function defaultActions()
	{
		return [
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
	}
}
