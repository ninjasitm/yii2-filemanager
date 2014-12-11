<?php

namespace nitm\filemanager\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use nitm\filemanager\models\Files;

/**
 * FilesSearch represents the model behind the search form about `nitm\filemanager\models\Files`.
 */
class FilesSearch extends \nitm\search\BaseElasticSearch
{
	public $engine = 'elasticsearch';
	public static $namespace = '\nitm\filemanager\\models\\';
}
