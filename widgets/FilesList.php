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
use nitm\filemanager\models\File;
use nitm\filemanager\models\search\File as FileSearch;
use nitm\helpers\Icon;

/**
 * Extends the kartik\widgets\FileInput widget.
 *
 * @author Malcolm Paul <lefteyecc@ninjasitm.com>
 * @since 1.0
 */
class FilesList extends BaseWidget
{
	public $options = [
		'id' => 'files'
	];

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
     * Runs the widget
     */
    public function run()
    {
		$searchModel = new FileSearch();
		if(isset($this->items))
			$dataProvider = new \yii\data\ArrayDataProvider([
				'allModels' => $this->items
			]);
		else if(isset($this->model) && $this->model instanceof File)
			$dataProvider = $searchModel->search([
				'remote_type' => $this->model->remote_type,
				'remote_id' => $this->model->remote_id
			]);
		else
			$datProvider = new \yii\data\ArrayDataProvider([
				'allModels' => $this->model->files()
			]);
		return \yii\widgets\ListView::widget([
			'dataProvider' => $dataProvider,
			'options' => [
				'tag' => 'div',
				'class' => 'list-group'
			],
			'itemOptions' => [
				'tag' => false,
			],
			'layout' => "{items}\n{pager}",
			'itemView' => function ($model) {
				return Html::tag('a', $model->file_name, [
					'class' => 'list-group-item',
					'target' => '_new',
					'href' => $model->url()
				]);
			}
		]);
    }

	protected function getAssets()
	{
		return [
			\nitm\filemanager\assets\FileAsset::className()
		];
	}
}
