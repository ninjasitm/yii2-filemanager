<?php

namespace nitm\filemanager\controllers;

use Yii;
use yii\base\InvalidConfigException;
use yii\helpers\FileHelper;
use yii\web\NotFoundHttpException;
use yii\web\HttpException;
use yii\web\view;
use yii\web\UploadedFile;
use yii\filters\VerbFilter;
use Aws\S3\S3Client;
use nitm\filemanager\helpers\Storage;
use nitm\filemanager\helpers\ImageHelper;
use nitm\filemanager\assets\FilemanagerAssets;
use nitm\filemanager\models\Image;
use nitm\filemanager\models\File;
use nitm\filemanager\models\search\File as FileSearch;
use nitm\helpers\ArrayHelper;
use nitm\helpers\Response;


/**
 * FileController implements the CRUD actions for File model.
 */
class FilesController extends DefaultController
{
	public function init()
	{
		parent::init();
		$this->model = new File();
	}

	public static function assets()
	{
		return [
			\nitm\filemanager\assets\FileAsset::className()
		];
	}

	public function actionIndex($type, $id)
	{
		if(\Yii::$app->request->isAjax)
			Response::viewOptions('js', 'initFileManager();');
		$files = parent::actionIndex(FileSearch::className(), $type, $id, [
			'construct' => [
				'queryOptions' => [
					'with' => [
						'author'
					],
				],
			],
		]);
		switch($this->getResponseFormat())
		{
			case 'json':
			$files = array_map(function ($file) {
				if($file) {
					//if($image->metadata == []) {
					//	\nitm\filemanager\helpers\ImageHelper::createThumbnails($image, $image->type);
					//	print_r($image->metadata);
					//}
					return [
						'link' => $file->url(),
						'name' => $file['file_name'],
						'title' => $file['file_name'],
						'size' => $file->getSize()
					];
				}
			}, $files);
			break;
		}
		return $files;
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
        $this->setResponseFormat('json');

        $model = $this->findModel($id);

        $awsConfig = $module->aws;

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
    public function actionCreate($type=null, $id=null)
    {
		$ret_val = [];

        Yii::$app->response->getHeaders()->set('Vary', 'Accept');
		$this->setResponseFormat('json');
		$module = \Yii::$app->getModule('nitm-files');

        $model = new File(['scenario' => 'create']);

		$type = !$type ? $model->isWhat() : $type;
		$id = !$id ? 0 : $id;

        $files = UploadedFile::getInstance($model, ArrayHelper::getValue($_REQUEST, 'fileParam', 'file_name'));
		if($files == [])
			$files = UploadedFile::getInstancesByName(ArrayHelper::getValue($_REQUEST, 'fileParam', 'file_name'));
		if($files == [])
			$files = UploadedFile::getInstancesByName($model->formName());

		$files = is_array($files) ? $files : [$files];

		foreach($files as $file)
		{
			$baseType =	$module->getBaseType($module->getExtension($file->type));
			$path       = $module->getPath($baseType).DIRECTORY_SEPARATOR.$type.DIRECTORY_SEPARATOR.$id;
			$url        = $module->url;
			$thumbnails = $module->thumbnails;

			$name = File::getSafeName($file->getBaseName()).'.'.$file->getExtension();

			$model->url             = FileHelper::normalizePath($path, $module->directorySeparator).$module->directorySeparator.$name;
			$model->file_name       = $name;
			$model->hash	= File::getHash($file->tempName);
			$model->title           = $file->name;
			$model->type            = $file->type;
			$model->title           = $file->name;
			$model->size            = $file->size;
			$model->html_icon = 'file-'.$baseType.'-o';
			$model->remote_type = $type;
			$model->remote_id = $id;
			$model->base_type		= $baseType;

			if($model->validate() && \nitm\filemanager\helpers\Storage::move($file->tempName, $model->getRealPath(), true) && $model->save())
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
					if($imageModel->save())
						ImageHelper::createThumbnails($imageModel, $imageModel->type, $imageModel->getRealPath());
				}
				$result = [
					'url'           => $url.$model->url,
					'thumbnailUrl'  => $url.$model->thumbnail_url,
					'name'          => $model->title,
					'title'          => $model->title,
					'type'          => $model->type,
					'size'          => $model->size,
					'deleteUrl'     => \Yii::$app->urlManager->createUrl(['filemanager/files/delete']),
					'deleteType'    => 'POST',
				];

			} else {
				error_log(print_r($model->getErrors(),true));
				$result = [
					'error' 		=> implode('. ', array_unique(array_map(function ($error) {
											return current($error);
										}, $model->getErrors()))),
					'name'          => $model->title,
					'type'          => $model->type,
					'size'          => $model->size,
				];
			}
			$ret_val['files'][] = $result;
		}

		return $ret_val;
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
     * Deletes an existing File model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        Yii::$app->response->getHeaders()->set('Vary', 'Accept');
        $this->setResponseFormat('json');

		$this->model = $this->findModel(File::className(), $id, ['metadata', 'icon']);

		ImageHelper::deleteImages($this->model->icon());

        $result = [
            'success' => Storage::delete($this->model->getRealPath()),
			'message' => 'Unable to delete '.$this->model->file_name,
			'indicate' => 'warning',
			'action' => 'delete'
        ];

        if($result['success'] || !$this->model->getFileExists()) {
			$result['message'] = 'Successfully deleted '.$this->model->file_name;
			$result['indicate'] = 'success';
        	$this->model->delete();
		}
        return $result;
    }

    public function actionProperties()
    {
        return $this->renderPartial('_properties');
    }

    protected function getMaximumFileUploadSize()
    {
        return min(convertPHPSizeToBytes(ini_get('post_max_size')), convertPHPSizeToBytes(ini_get('upload_max_filesize')));
    }


}
