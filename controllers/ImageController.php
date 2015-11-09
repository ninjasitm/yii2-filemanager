<?php

namespace nitm\filemanager\controllers;

use yii\helpers\FileHelper;
use yii\helpers\Html;
use nitm\helpers\Response;
use nitm\filemanager\helpers\ImageHelper;
use nitm\filemanager\models\Image;
use nitm\filemanager\models\ImageMetadata;
use nitm\filemanager\models\search\Image as ImageSearch;
use nitm\filemanager\helpers\Storage;

class ImageController extends DefaultController
{
	use \nitm\traits\Controller;

	public function init()
	{
		parent::init();
		$this->model = new Image(['scenario' => 'default']);
	}

	public function behaviors()
	{
		$behaviors = [
			'access' => [
				'class' => \yii\filters\AccessControl::className(),
				'only' => ['get'],
				'rules' => [
					[
						'actions' => ['get'],
						'allow' => true,
						'roles' => ['?', '@'],
					],
					[
						'actions' => ['delete', 'default', 'save'],
						'allow' => true,
						'roles' => ['@'],
					],
				],
			],
			'verbs' => [
				'class' => \yii\filters\VerbFilter::className(),
				'actions' => [
					'get' => ['get'],
					'delete' => ['post'],
					'default' => ['post'],
					'save' => ['post', 'get'],
				],
			],
		];

		return array_replace_recursive(parent::behaviors(), $behaviors);
	}

	public static function assets()
	{
		return array_merge(parent::assets(), [
			\nitm\filemanager\assets\ImageAsset::className()
		]);
	}

	public function actionGallery($type, $id)
	{
		return \nitm\filemanager\widgets\Images::widget([
			'model' => $this->findModel(\Yii::$app->getModule('nitm-files')->getModelClass($type), $id)
		]);
	}

	public function actionIndex($type, $id)
	{
		return parent::actionIndex(ImageSearch::className(), $type, $id);
	}


    public function actionGet($id, $filename=null)
    {
		$size = \Yii::$app->request->get('size');
		$model = $this->findModel(Image::className(), $id, ['metadata']);
		switch($model instanceof Image)
		{
			case true:
			$image = $model->getIcon($size);
			\Yii::$app->response->getHeaders()->set('Content-Type', $model->type);
			switch(1)
			{
				case file_exists($image->getRealPath()):
				if(!\Yii::$app->request->get('__format'))
					$_GET['__format'] = 'raw';
				return $this->getContents($image);
				break;

				default:
				return Image::getHtmlIcon($image->html_icon);
				break;
			}
			break;
		}
    }

	protected function getContents($image)
	{
		$contents = file_get_contents($image->getRealPath());
		switch(\Yii::$app->request->get('__format') == 'html')
		{
			//We should display the image rather than the raw contents
			case true:
			return '<img url="'."data:".$image->type.";base64,".base64_encode($contents).'"/>';
			break;

			default:
			return $contents;
			break;
		}
	}

	/**
	 * Save images for a model
	 *
	 */
	public function actionSave($type, $id)
	{
		$ret_val = [
			'files' => []
		];
		if(is_null($class = \Yii::$app->getModule('nitm-files')->getModelClass($type)))
			return false;
		$model = $class::findOne($id);
		$imageModels = ImageHelper::saveImages($model, $type, $id);
		if(is_array($imageModels) && $imageModels != []) {
			$ret_val['success'] = true;
			$ret_val['data'] = '';
			$imageWidget = new \nitm\filemanager\widgets\Images(['model' => $model]);
			$renderer = \Yii::$app->request->isAjax ? 'renderAjax' : 'render';
			foreach($imageModels as $image)
			{
				$ret_val['files'][] = [
					'name' => $image->file_name,
					'size' => $image->size,
					'url' => $image->url(),
					'thumbnailUrl' => $image->url('small'),
					'deleteUrl' => implode(DIRECTORY_SEPARATOR, [
						$this->id,
						'delete',
						$image->getId()
					]),
					'deleteType' => 'POST'
				];
				$ret_val['data'] .= $imageWidget->getImage($image);
			}
			Response::viewOptions([
				"view" => 'index',
				"args" => [
					"dataProvider" => new \yii\data\ArrayDataProvider(["allModels" => $imageModels]),
				]
			]);
		}
		$this->setResponseFormat(\Yii::$app->request->isAjax ? 'json' : 'html');
		return $this->renderResponse($ret_val, Response::viewOptions(), \Yii::$app->request->isAjax);
	}

	public function actionDefault($id)
	{
		$this->setResponseFormat('json');
		$model = $this->findModel(Image::className(), $id, ['metadata']);
		if($model instanceof Image) {
			Image::updateAll([
				'is_default' => 0
			], [
				'remote_type' => $model->remote_type,
				'remote_id' => $model->remote_id
			]);
			$model->setScenario('update');
			$model->is_default = 1;
			return $model->save();
		}
	}

	public function actionDelete($id)
	{
		$this->setResponseFormat('json');
		$model = $this->findModel(Image::className(), $id);
		if($model instanceof Image) {
			return ImageHelper::deleteImages($model);
		}
		return false;
	}

	/*
	 * Get the forms associated with this controller
	 * @param string $param What are we getting this form for?
	 * @param int $unique The id to load data for
	 * @param array $options
	 * @return string | json
	 */
	public function actionForm($type, $remoteType, $remoteId)
	{
		return parent::actionForm($type, $remoteId, [
			'modelClass' => \Yii::$app->getModule('nitm-files')->getModelClass($remoteType)
		]);
	}

}
