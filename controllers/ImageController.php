<?php

namespace nitm\filemanager\controllers;

use yii\helpers\FileHelper;
use yii\helpers\Html;
use nitm\helpers\Response;
use nitm\filemanager\helpers\ImageHelper;
use nitm\filemanager\models\Image;
use nitm\filemanager\models\ImageMetadata;
use nitm\filemanager\helpers\Storage;
use nitm\filemanager\models\search\Image as ImageSearch;

class ImageController extends DefaultController
{	
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
						'actions' => ['default', 'get'],
						'allow' => true,
						'roles' => ['@'],
					],
				],
			],
			'verbs' => [
				'class' => \yii\filters\VerbFilter::className(),
				'actions' => [
					'default' => ['post'],
				],
			],
		];
		
		return array_replace_recursive(parent::behaviors(), $behaviors);
	}
	
	public static function assets()
	{
		return [
			\nitm\filemanager\assets\ImageAsset::className()
		];
	}
	
	public function actionIndex($type, $id)
	{
		$images = parent::actionIndex(ImageSearch::className(), $type, $id, [
			'construct' => [
				'queryOptions' => [
					'with' => [
						'author'
					],
				],
			]
		]);
		switch($this->getResponseFormat())
		{
			case 'json':
			$images = array_map(function ($image) {
				if($image) {
					//if($image->metadata == []) {
					//	\nitm\filemanager\helpers\ImageHelper::createThumbnails($image, $image->type);
					//	print_r($image->metadata);
					//}
					return [
						'thumb' => $image->url('small', 'remote'),
						'image' => $image->url(null, 'remote'),
						'title' => $image->file_name
					];
				}
			}, $images);
			break;
		}
		return $images;
	}
	
	/**
	 * Save images for a model
	 * 
	 */
	public function actionCreate($type, $id)
	{
		$ret_val = [
			'remoteId' => $id
		];
		if(is_null($class = \Yii::$app->getModule('nitm-files')->getModelClass($type)))
			return ['fileLink' => '#', 'filename' => 'Failed'];
		$model = $class::findOne($id);
		$imageModels = ImageHelper::saveImages($model, $type, $id);
		switch(is_array($imageModels) && $imageModels != [])
		{
			case true:
			$ret_val['success'] = true;
			$ret_val['data'] = '';
			$imageWidget = new \nitm\filemanager\widgets\Images(['model' => $model]);
			$renderer = \Yii::$app->request->isAjax ? 'renderAjax' : 'render';
			foreach($imageModels as $image)
			{
				$ret_val['files'][] = [
					'name' => $image->file_name,
					'filename' => $image->file_name,
					'size' => $image->size,
					'url' => $image->url,
					'filelink' => $image->url,
					'thumbnailUrl' => $image->getIcon('medium')->url,
					'deleteUrl' => implode(DIRECTORY_SEPARATOR, [
						$this->id,
						'delete',
						$image->getId()
					]),
					'deleteType' => 'POST'
				];
				$ret_val['data'] .= $imageWidget->getThumbnail($image);
			}
			Response::viewOptions([
				"view" => 'index', 
				"args" => [
					"dataProvider" => new \yii\data\ArrayDataProvider(["allModels" => $imageModels]),
				]
			]);
			break;
			
			default:
			break;
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
		$ret_val = [
			'action' => 'delete',
			'success' => 'false'
		];
		$this->setResponseFormat('json');
		$model = $this->findModel(Image::className(), $id);
		if($model instanceof Image) {
			$ret_val['success'] =  ImageHelper::deleteImages($model);
		}
		return $ret_val;
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
