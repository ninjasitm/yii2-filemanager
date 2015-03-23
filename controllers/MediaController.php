<?php

namespace nitm\filemanager\controllers;

use Yii;
use yii\base\InvalidConfigException;
use yii\helpers\FileHelper;
use yii\helpers\ArrayHelper;
use yii\web\NotFoundHttpException;
use yii\web\HttpException;
use yii\web\Response as YiiResponse;
use yii\web\view;
use yii\web\UploadedFile;
use yii\filters\VerbFilter;
use Aws\S3\S3Client;
use nitm\helpers\Response;
use nitm\filemanager\helpers\ImageHelper;
use nitm\filemanager\assets\FilemanagerAssets;
use nitm\filemanager\models\Image;
use nitm\filemanager\models\File;
use nitm\filemanager\models\FileMetadata;
use nitm\filemanager\models\search\File as FileSearch;


/**
 * FileController implements the CRUD actions for File model.
 */
class MediaController extends \nitm\controllers\DefaultController
{
    public $page_size = 12;
	protected $client;
	
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
							'video-upload-step-one',
							'video-upload-step-two',
							'video-link-step-one',
							'video-link-step-two',
							'video-callback',
						],
						'allow' => true,
						'roles' => ['@'],
					],
				],
			],
			'verbs' => [
				'actions' => [
					'video-upload-step-one' => ['get'],
					'video-upload-step-two' => ['post', 'get'],
					'video-link-step-one' => ['get'],
					'video-link-step-two' => ['post', 'get'],
				],
			],
        ]);
    }
	
	protected function getUser($id=null)
	{
		if($id != null) {
			$userClass = \Yii::$app->user->identityClass;
			$user = $userClass::findOne($id);
		}
		else
			$user = \Yii::$app->user->getIdentity();
			
		return $user;
	}
	
	protected function getRedirectUrl()
	{
		switch(\Yii::$app->getModule('nitm-files')->setting('provider'))
		{
			case 'youtube':
			$this->getClient()->addScope(\Google_Service_YouTube::YOUTUBE);
			$this->getClient()->setRedirectUri(\Yii::$app->urlManager->createAbsoluteUrl([$this->id.'/'.$this->action->id]));
			$this->redirect($this->getClient()->createAuthUrl());
			break;
		}
	}
	
	protected function getAuthentication()
	{
		switch(\Yii::$app->getModule('nitm-files')->setting('provider'))
		{
			case 'youtube':
			$this->getClient()->authenticate(\Yii::$app->request->get('code'));
			\Yii::$app->session->set('video-auth-token', $this->getClient()->getAccessToken());
			break;
		}
	}
	
	protected function getClientAuth($id=null)
	{
		if(!\Yii::$app->session->get('video-auth-token')) {
			\Yii::$app->session->set('video-auth-user-id', $id);
			$this->getRedirectUrl();
		}
		
		if (\Yii::$app->request->get('code')) {
			if (strval(\Yii::$app->session->get('video-auth-state')) !== strval(\Yii::$app->request->get('video-auth-state'))) {
				throw new \yii\base\ErrorException('The session state did not match.');
			}
			\Yii::$app->session->set('video-auth-user-id', $id);
			$this->redirect(\Yii::$app->request->absoluteUrl.'?id='.$id);
		}
	}
	
	public function actionVideoUploadStepOne($id=null)
	{
		$user = $this->getUser($id);
		
		$this->getClientAuth($user->getId());
		
		Response::viewOptions('args', [
			'content' => \nitm\filemanager\widgets\VideoUpload::widget([
				'user' => $user,
				'step' => 'start',
				'method' => 'upload',
				'action' => '/media/video-upload-step-two?id='.$user->getId(),
				'model' => $this->model,
				'metadataModel' => new \nitm\filemanager\models\FileMetadata
			]),
		]);
		return $this->renderResponse(null, Response::viewOptions(), \Yii::$app->request->isAjax);
	}
	
	public function actionVideoLinkStepOne($id=null)
	{
		$user = $this->getUser($id);
		
		$this->getClientAuth($user);
		
		Response::viewOptions('args', [
			'content' => \nitm\filemanager\widgets\VideoUpload::widget([
				'user' => $user,
				'step' => 'start',
				'method' => 'add',
				'action' => '/media/video-link-step-two?id='.$user->getId(),
				'model' => $this->model,
				'metadataModel' => new \nitm\filemanager\models\FileMetadata
			]),
		]);
		return $this->renderResponse(null, Response::viewOptions(), \Yii::$app->request->isAjax);
	}
	
	public function actionVideoUploadStepTwo($id=NULL)
	{
		$user = $this->getUser($id);
		$this->model->setScenario('create');
		$this->model->load(\Yii::$app->request->post(), false);
		
		$metadataModel = new FileMetadata();
		$metadataModel->setAttributes([
			'key' => 'description',
			'value' => ArrayHelper::getValue(\Yii::$app->request->post($metadataModel->formName()), 'value')
		], false);
			
		Response::viewOptions('args', [
			'content' => \nitm\filemanager\widgets\VideoUpload::widget([
				'user' => $user,
				'step' => 'upload',
				'model' => $this->model,
				'googleClient' => $this->getClient(),
				'authToken' => \Yii::$app->session->get('video-upload-token'),
				'afterUploadUrl' => \Yii::$app->urlManager->createAbsoluteUrl([$this->id.'/video-calllback']),
				'metadataModel' => $metadataModel 
			]),
		]);
		return $this->renderResponse(null, Response::viewOptions(), \Yii::$app->request->isAjax);
	}
	
	public function actionVideoLinkStepTwo($id=NULL)
	{
		$user = $this->getUser($id);
		$this->model->setScenario('create');
		$this->model->load(\Yii::$app->request->post(), false);
		
		$metadataModel = new FileMetadata();
		$metadataModel->setAttributes([
			'key' => 'description',
			'value' => ArrayHelper::getValue(\Yii::$app->request->post($metadataModel->formName()), 'value')
		], false);
			
		Response::viewOptions('args', [
			'content' => \nitm\filemanager\widgets\VideoUpload::widget([
				'user' => $user,
				'step' => 'upload',
				'model' => $this->model,
				'googleClient' => $this->getClient(),
				'authToken' => \Yii::$app->session->get('video-upload-token'),
				'afterUploadUrl' => \Yii::$app->urlManager->createAbsoluteUrl([$this->id.'/video-calllback']),
				'metadataModel' => $metadataModel 
			]),
		]);
		return $this->renderResponse(null, Response::viewOptions(), \Yii::$app->request->isAjax);
	}
	
	protected function getClient($type='youtube', $for='video')
	{
		if(\Yii::$app->getModule('nitm-files')->isSupportedProvider($for, $type))
		{
			switch($type)
			{
				case 'youtube':
				if(!isset($this->client[$type])) {
					$this->client[$type] = new \Google_Client();				
					$this->client[$type]->setAuthConfig(\Yii::$app->getModule('nitm-files')->setting('youtube.authConfig'));
					$this->client[$type]->setDeveloperKey(\Yii::$app->getModule('nitm-files')->setting('youtube.developerKey'));
				}
				break;
			}
			return $this->client[$type];
		}
		throw new \yii\base\InvalidConfigException("$type is nto a valid client");
	}

    /**
     * You tube callback
     * @return mixed
     */
    public function actionProviderCallback()
    {
		$id = \Yii::$app->session->set('video-upload-user-id');
		$user = $this->getUser($id);
		if(Cache::cache()->exists('video-upload-step-1-'.$user->getId()))
		{
			$this->getClient()->setScopes(\Google\Google_Service_YouTube::YOUTUBE);
			$redirectUri = \Yii::$app->request->absoluteUrl;
			$this->getClient()->setRedirectUri($redirect);
			
			print_r("HERE");
				
				\Yii::$app->request->setBodyParams(Cache::cache()->get('video-upload-step-1-'.$user->getId()));
				Cache::cache()->delete('video-upload-step-1-'.$user->getId());
				
				/**
				 * Only now do we create the file locally
				 */
				$ret_val = parent::actionCreate();
				$this->model->user_id = $user->getId();
				$this->model->save();
			
				\Yii::$app->session->remove('video-upload-user-id');
				
				return $this->render('index', [
					'searchModel' => $searchModel,
					'dataProvider' => $dataProvider,
					'model' => $model,
				]);
		}
		else
			return null;
    }   
}
