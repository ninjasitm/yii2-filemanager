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
use nitm\filemanager\models\Image;
use nitm\filemanager\models\search\Image as ImageSearch;
use nitm\helpers\Icon;

/**
 * Extends the kartik\widgets\ImageInput widget.
 *
 * @author Malcolm Paul <lefteyecc@ninjasitm.com>
 * @since 1.0
 */
class ImagesList extends BaseWidget
{
	public $options = [
		'id' => 'image'
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
		$searchModel = new ImageSearch();
		if(isset($this->items))
			$dataProvider = new \yii\data\ArrayDataProvider([
				'allModels' => $this->items
			]);
		else if(isset($this->model) && $this->model instanceof Image)
			$dataProvider = $searchModel->search([
				'remote_type' => $this->model->remote_type,
				'remote_id' => $this->model->remote_id
			]);
		else
			$datProvider = new \yii\data\ArrayDataProvider([
				'allModels' => $this->model->images()
			]);
		return $this->render('@nitm/filemanager/views/image/index', [
			'type' => $this->model->remote_type,
			'id' => $this->model->remote_id,
			'dataProvider' => $dataProvider,
			'searchModel' => $searchModel,
			'model' => $this->model,
			'noBreadcrumbs' => true
		]);
    }

	protected function getAssets()
	{
		return [
			\nitm\filemanager\assets\ImageAsset::className()
		];
	}
}
