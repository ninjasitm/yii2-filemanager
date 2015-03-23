<?php

namespace nitm\filemanager\controllers;

use Yii;
use yii\base\InvalidConfigException;
use yii\helpers\FileHelper;
use yii\web\NotFoundHttpException;
use yii\web\HttpException;
use yii\web\Response;
use yii\web\view;
use yii\web\UploadedFile;
use yii\filters\VerbFilter;
use Aws\S3\S3Client;
use nitm\filemanager\helpers\ImageHelper;
use nitm\filemanager\assets\FilemanagerAssets;
use nitm\filemanager\models\Image;
use nitm\filemanager\models\File;
use nitm\filemanager\models\search\File as FileSearch;


/**
 * FileController implements the CRUD actions for File model.
 */
class FilesController extends \nitm\controllers\DefaultController
{

    public $page_size = 12;
	
	public function init() 
	{
		parent::init();
		$this->model = new File();
	}
    
    public function behaviors()
    {
        return array_merge_recursive(parent::behaviors(), [
			'access' => [
				'rules' => [
					[
						'actions' => [
							'upload',
						],
						'allow' => true,
						'roles' => ['@'],
					],
				],
			],
			'verbs' => [
				'actions' => [
					'upload' => ['post'],
				],
			],
        ]);
    }
    
    /**
     * beforeAction function.
     * 
     * @access public
     * @param mixed $action
     * @return void
     */
    public function beforeAction($action) 
	{
		switch($action->id)
		{
			case 'upload':
			$this->enableCsrfValidation = false;
			break;
		}
        $result = parent::beforeAction($action);
		
        $options = [
           'tinymce'             => \Yii::$app->urlManager->createUrl('/filemanager/files/tinymce'),
           'properties'          => \Yii::$app->urlManager->createUrl('/filemanager/files/properties'),
        ];
        $this->getView()->registerJs("filemanager.init(".json_encode($options).");", \yii\web\View::POS_END, 'my-options');
        return $result;
    }

    /**
     * Lists all File models.
     * @return mixed
     */
    public function actionIndex()
    {
        FilemanagerAssets::register($this->view);
		return parent::actionIndex(FileSearch::className(), [
			'with' => [
				'author', 'icon'
			],
			'defaultParams' => [$this->model->formName() => ['deleted' => 0]]
		]);
    }

    /**
     * Lists all File models.
     * @return mixed
     */
    public function actionTinymce()
    {
    
        $this->layout = 'tinymce';

        FilemanagerAssets::register($this->view);
        
        $searchModel = new FileSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $model = new File();
        
        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'model' => $model,
        ]);
    }
    
    /**
     * actionFilemodal function.
     * 
     * @access public
     * @return void
     */
    public function actionFilemodal(){
    
        $this->layout = 'tinymce';

        FilemanagerAssets::register($this->view);
        
        $searchModel = new FileSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $model = new File();
        
        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'model' => $model,
        ]);
    }
    
    public function actionGetimage($id)
	{
        
        Yii::$app->response->getHeaders()->set('Vary', 'Accept');
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $model = $this->findModel($id);
        
        $awsConfig = $this->module->aws;
        
        if($awsConfig['enable']){
            $model->url = $awsConfig['url'].$model->url;
        }else{
            $model->url = '/'.$model->url;
        }
        
        return $model;
    }
    
    /**
     * actionUpload function.
     * 
     * @access public
     * @return string JSON
     */
    public function actionUpload($type=null, $id=null)
    {
        Yii::$app->response->getHeaders()->set('Vary', 'Accept');
        Yii::$app->response->format = Response::FORMAT_JSON;
		
        $model = new File();
        
		$type = !$type ? $model->isWhat() : $type;
		$id = !$id ? 0 : $id;
		
        $file = UploadedFile::getInstance($model,'file_name');
        
		$baseType =	$this->module->getBaseType($this->module->getExtension($file->type));
        $path       = $this->module->getPath($baseType).DIRECTORY_SEPARATOR.$type.DIRECTORY_SEPARATOR.$id;
        $url        = $this->module->url;
        $thumbnails = $this->module->thumbnails;
		
        $name = File::getSafeName($file->getBaseName()).'.'.$file->getExtension();

        $model->url             = FileHelper::normalizePath($path, $this->module->directorySeparator).$this->module->directorySeparator.$name;
        $model->file_name       = $name;
		$model->hash	= File::getHash($file->tempName);
        $model->title           = $file->name;
        $model->type            = $file->type;
        $model->title           = $file->name;
        $model->size            = $file->size;
		$model->html_icon = 'file-'.$baseType.'-o';
		$model->remote_type = $model->isWhat();
		$model->base_type		= $baseType;
		
        /*$model->width         = $size[0];
        $model->height          = $size[1];*/
		
		//if(1){
        if(\nitm\filemanager\helpers\Storage::move($file->tempName, $model->url, true) && $model->save())
		{
			if($model->base_type == 'image') {
				$size = getimagesize($model->getRealpath());
				$imageModel = new Image([
					'remote_type' => $model->isWhat(),
					'remote_id' => $model->getId(),
					'type' => $model->type,
					'url' => $model->url,
					'is_default' => true,
					'hash' => $model->hash,
					'width' => $size[0],
					'height' => $size[1],
					'size' => $model->size,
					'file_name' => $file->name,
					'slug' => $model->isWhat().'-'.$file->name
				]);
				if($imageModel->save()) {
					ImageHelper::createThumbnails($imageModel);
				}
			}
            $response['files'][] = [
                'url'           => $url.$model->url,
                'thumbnailUrl'  => $url.$model->thumbnail_url,
                'name'          => $model->title,
                'type'          => $model->type,
                'size'          => $model->size,
                'deleteUrl'     => \Yii::$app->urlManager->createUrl(['filemanager/files/delete']),
                'deleteType'    => 'POST',
            ];
            
            return $response;
            
        }else{
            
            error_log(print_r($model->getErrors(),true));
            
            return false;
        }
        
    }

    /**
     * Displays a single File model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id)
    {
        
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Creates a new File model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate($type=null, $id=null)
    {
        $model = new File([
			'remote_type' => $type,
			'remote_id' => $id
		]);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        } else {
            return $this->render('create', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Updates an existing File model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        } else {
            return $this->render('update', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Deletes an existing File model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        Yii::$app->response->getHeaders()->set('Vary', 'Accept');
        Yii::$app->response->format = Response::FORMAT_JSON;
        
		$model = $this->findModel(File::className(), $id, ['metadata', 'icon']);
        
		Image::deleteImages($model->icon());
		
        $result = [
            'success' => Storage::delete($model->getPath()),
        ];
        
        $model->delete();
        
        return $result;
    }
    
    public function actionProperties()
    {
        return $this->renderPartial('_properties');
    }
    


    /**
     * Finds the File model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return File the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = File::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    } 

    protected function getMaximumFileUploadSize()  
    {  
        return min(convertPHPSizeToBytes(ini_get('post_max_size')), convertPHPSizeToBytes(ini_get('upload_max_filesize')));  
    }  
    
    
}
