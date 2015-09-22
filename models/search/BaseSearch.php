<?php

namespace nitm\filemanager\models\search;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * BaseElasticSearch provides the basic search functionality based on the class it extends from.
 */
class BaseSearch extends \nitm\search\BaseSearch
{
	use \nitm\widgets\traits\BaseWidget, \nitm\filemanager\traits\FileTraits, \nitm\traits\Nitm {
		\nitm\filemanager\traits\FileTraits::url insteadof \nitm\traits\Nitm;
	}
	
	public $engine = 'elasticsearch';
	public static $namespace = '\nitm\filemanager\models\\';
	
	/*public function init()
	{
		$this->namespace = "\\lab1\models\\";
		$this->primaryModelClass = $this->namespace.$this->formName();
		$class = $this->primaryModelClass;
		$this->primaryModel = new $class;
		parent::init();
	}
	
	public function behaviors()
	{
		$behaviors = [];
		switch($this->engine)
		{
			case 'elasticsearch':
			$behaviors['search'] = [
				'class' => \nitm\search\BaseElasticSearch::className(),
				'primaryModelClass' => $this->namespace.$this->formName()
			];
			break;
			
			default:
			$behaviors['search'] = [
				'class' => \nitm\search\BaseSearch::className(),
				'primaryModelClass' => $this->namespace.$this->formName()
			];
			break;
		}
		return array_merge(parent::behaviors(), $behaviors);
	}*/
}