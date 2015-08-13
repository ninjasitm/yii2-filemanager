<?php
/**
* @link http://www.yiiframework.com/
* @copyright Copyright (c) 2008 Yii Software LLC
* @license http://www.yiiframework.com/license/
*/

namespace nitm\filemanager\widgets;

use Yii;
use yii\base\InvalidConfigException;
use yii\helpers\Html;
use nitm\models\User;
use nitm\filemanager\models\Images as ImagesModel;
use kartik\icons\Icon;

class ImagesCount extends BaseWidget
{
	/*
	 * HTML options for generating the widget
	 */
	public $options = [
		'class' => 'btn btn-sm',
		'role' => 'imagesCount',
		'id' => 'images-count',
		'tag' => 'a'
	];
	
	public $widgetOptions = [
		'class' => 'btn-group text-center'
	];
	
	public function init()
	{
		switch(1)
		{
			case !($this->model instanceof ImagesModel) && (($this->parentType == null) || ($this->parentId == null)):
			$this->model = null;
			break;
			
			default:
			$this->model = ($this->model instanceof ImagesModel) ? $this->model : ImagesModel::findModel([$this->parentId, $this->parentType]);
			break;
		}	
		parent::init();
	}
	
	public function run()
	{
		$this->options['id'] .= $this->parentId;
		$this->options['class'] .= ' '.($this->model->count() >= 1 ? 'btn-primary' : 'btn-transparent');
		$this->options['label'] = $this->getLabel();
		$this->options['href'] = \Yii::$app->urlManager->createUrl(['/images/index/'.$this->parentType."/".$this->parentId, '__format' => 'modal']);
		$this->options['title'] = \Yii::t('yii', 'View Images');
		$info = $this->getInfoLink();
		return $info = Html::tag('div', $info, $this->widgetOptions).$this->getNewIndicator();
	}
}
?>