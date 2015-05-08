<?php

namespace nitm\filemanager\controllers;

use yii\helpers\FileHelper;
use yii\helpers\Html;
use nitm\helpers\Response;
use nitm\filemanager\helpers\ImageHelper;
use nitm\filemanager\models\Image;
use nitm\filemanager\models\ImageMetadata;
use nitm\filemanager\helpers\Storage;

class ImageController extends \nitm\controllers\DefaultController
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
	
	
    public function actionGet($id, $size=null)
    {
		$model = $this->findModel(Image::className(), $id, ['metadata']);
		switch($model instanceof Image)
		{
			case true:
			$image = $model->getIcon($size);
			\Yii::$app->response->getHeaders()->set('Content-Type', $image->type);
			switch(1)
			{
				case file_exists($image->getRealPath()):
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
		switch(\Yii::$app->request->get('__format') == 'raw')
		{
			//We should display the image rather than the raw contents
			case false:
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
		$ret_val = false;
		if(is_null($class = \Yii::$app->getModule('nitm-files')->getModelClass($type)))
			return false;
		$model = $class::findOne($id);
		$imageModels = ImageHelper::saveImages($model, $type, $id);
		switch(is_array($imageModels) && $imageModels != [])
		{
			case true:
			$ret_val['success'] = true;
			$ret_val['data'] = '';
			$imageWidget = new \nitm\filemanager\widgets\Images(['model' => array_pop($imageModels)]);
			$renderer = \Yii::$app->request->isAjax ? 'renderAjax' : 'render';
			foreach($imageModels as $image)
			{
				switch($image->isDefault())
				{
					case true:
					$renderOpts = $imageWidget->getDefaultImageRenderOptions($image);
					break;
					
					default:
					$renderOpts = $imageWidget->getExtraImageRenderOptions($image, $image->id);
					break;
				}
				$renderOpts['options']['withActions'] = true;
				$ret_val['data'] .= $this->$renderer("/image/thumbnail", $renderOpts['options']);
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
		switch($model instanceof Image)
		{
			case true:
			switch(1)
			{
				case !is_null($model->content_id):
				$idField = 'content_id';
				break;
				
				default:
				$idField = 'category_id';
				break;
			}
			Image::updateAll([
				'is_default' => 0
			], [$idField => $model->$idField]);
			$model->setScenario('delete');
			$model->is_default = 1;
			return $model->save();
			break;
		}
	}
	
	public function actionDelete($id)
	{
		$this->setResponseFormat('json');
		$model = $this->findModel(Image::className(), $id);
		switch($model instanceof Image)
		{
			case true:
			ImageMetadata::deleteAll(['image_id' => $model->id]);
			switch($model->delete())
			{
				case true:
				return Storage::delete($model->url);
				break;
			}
			break;
		}
	}

}
