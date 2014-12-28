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
	
	/**
	 * Sizes supported
	 */
	private $_sizes = [
		'tiny' => 'thumbnail-xs',
		'small' => 'thumbnail-sm',
		'default' => 'thumbnail',
		'large' => 'thumbnail-lg',
	];
	
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

	public function run()
	{
		$this->model = $this->model instanceof Image ? $this->model : new Image();
		$this->size = isset($this->_sizes[$this->size]) ? $this->size : 'small';
		switch($this->size)
		{
			case 'small':
			case 'tiny':
			$size = $this->_sizes[$this->size];
			$this->size = 'small';
			break;
			
			case 'medium':
			case 'normal':
			$size = $this->_sizes[$this->size];
			$this->size = 'large';
			break;
			
			default:
			$this->size = 'default';
			$size = $this->_sizes[$this->size];
			break;
		}
		$this->options['class'] .= ' '.$size;
		switch($this->model->getIsNewRecord())
		{
			case true:
			$thumbnail = Html::tag('div', Image::getHtmlIcon($this->htmlIcon), $this->options);
			break;
			
			default:
			$thumbnail = Html::tag('div', 
				Html::img(
					'/image/get/'.$this->model->getId().'/'.$this->size."?__format=raw", 
					$this->imageOptions
				), 
				$this->options
			);
			break;
		}
		if(isset($this->title))
			$thumbnail .= $this->title; 
		return $thumbnail;
	}
}
