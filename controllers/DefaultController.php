<?php

namespace nitm\filemanager\controllers;

use yii;
use nitm\filemanager\assets\FilemanagerAssets;
use nitm\filemanager\models\search\File as FileSearch;

class DefaultController extends \nitm\controllers\DefaultController
{
    public $pageSize = 12;

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
						'actions' => ['save', 'upload'],
						'allow' => true,
						'roles' => ['@'],
					],
				],
			],
			'verbs' => [
				'class' => \yii\filters\VerbFilter::className(),
				'actions' => [
					'get' => ['get'],
					'save' => ['post'],
					'upload' => ['post']
				],
			],
		];

		return array_replace_recursive(parent::behaviors(), $behaviors);
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
			case 'save':
			case 'create':
			$this->enableCsrfValidation = false;
			break;
		}
        $result = parent::beforeAction($action);

        $options = [
           'tinymce'             => \Yii::$app->urlManager->createUrl('files/tinymce'),
           'properties'          => \Yii::$app->urlManager->createUrl('/files/properties'),
        ];
        $this->getView()->registerJs("filemanager.init(".json_encode($options).");", \yii\web\View::POS_END, 'my-options');
        return $result;
    }

	public function getWith()
	{
		return array_merge(parent::getWith(), []);
	}

    /**
     * Lists all File models.
     * @return mixed
     */
    public function actionIndex($searchClass, $type, $id, $options=[])
    {
		unset($_GET['type'], $_GET['id']);
		if(\Yii::$app->request->isAjax) {
			$asset = '\\nitm\\filemanager\\assets\\FileAsset';
			$asset::register($this->getView());
		}
		$this->model->setAttributes([
			'remote_type' => $type,
			'remote_id' => $id
		], false);
		return parent::actionIndex($searchClass, array_merge_recursive([
			'construct' => [
				'inclusiveSearch' => false,
				'booleanSearch' => false,
			],
			'params' => [
				$this->model->formName() => [
					'remote_type' => $type,
					'remote_id' => $id
				]
			],
			'defaults' => [
				'params' => [$this->model->formName() => ['deleted' => 0]]
			],
			'viewOptions' => [
				'noBreadcrumbs' => \Yii::$app->request->isAjax,
				'type' => $type,
				'id' => $id
			]
		], $options));
    }

	/**
	 * Synonymous save function
	 *
	 */
	public function actionSave($type, $id)
	{
		return $this->actionCreate($type, $id);
	}

	/**
	 * Synonymous save function
	 *
	 */
	public function actionUpload($type, $id)
	{
		return $this->actionCreate($type, $id);
	}

	/**
	 * Download a file
	 * @param string | int $id
	 */
    public function actionGet($id)
    {
		$parts = explode(':', $id);
		$size = \Yii::$app->request->get('size');
		$filename = \Yii::$app->request->get('filename');
		if(is_numeric($parts[0]))
			$id = (int) array_shift($parts);
		else
			$id = array_pop($parts);

		switch(sizeof($parts))
		{
			//Parts will be size 1 if there were two parts before
			case 1:
			$queryOptions = [
				'orWhere' => [
					'remote_id' => $id,
					'remote_type' => current($parts)
				]
			];
			break;

			default:
			if(is_string($id) && !is_numeric($id))
			$queryOptions = [
				'orWhere' => ['file_name' => $id]
			];
			else
				$queryOptions = [];
			break;
		}

		if($filename && $queryOptions!=[])
			$queryOptions['andWhere'] = ['file_name' => $filename];

		$model = $this->findModel($this->model->className(), $id, ['metadata'], $queryOptions);
		$className = $this->model->className();
		if($model)
		{
			$this->setContentType($model);
			\Yii::$app->response->getHeaders()->set('Content-Disposition', 'attachment; filename="'.$model->file_name.'"');

			if($model instanceof Image)
				$model = $model->getIcon($size);

			switch($this->getResponseFormat())
			{
				case 'icon':
				return Image::getHtmlIcon($model->html_icon);
				break;

				default:
				return $this->getContents($model);
				break;
			}
		}
    }

	/**
	 * Set the content type of download
	 * @param File|Image $model
	 */
	protected function setContentType($model)
	{
		if(\Yii::$app->request->get('__format') == 'html') {
			$this->setResponseFormat('html');
			\Yii::$app->response->getHeaders()->set('Content-Type', 'text/html');
		} else {
			$this->setResponseFormat('raw');
			\Yii::$app->response->getHeaders()->set('Content-Type', $model->type);
		}
	}

	/**
	 * Get the contents of a file
	 * @param File|Image $model
	 * @return string content
	 */
	protected function getContents($model)
	{
		$contents = file_get_contents($model->getRealPath());
		if($model instanceof Image)
		{
			if(\Yii::$app->request->get('__format') == 'html')
				//We should display the image rather than the raw contents
				return '<img url="'."data:".$model->type.";base64,".base64_encode($contents).'"/>';
			else
				return $contents;
		} else
			return $contents;
	}
}
