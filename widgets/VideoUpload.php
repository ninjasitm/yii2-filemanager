<?php
/**
 * @copyright Copyright &copy; Malcolm Paul, Nitm Inc, 2014
 * @version 1.0.0
 */

namespace nitm\filemanager\widgets;

use Yii;
use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use nitm\filemanager\models\File;
use nitm\filemanager\models\FileMetadata;
use nitm\helpers\Helper;
use nitm\helpers\Response;
use nitm\helpers\Cache;
use nitm\helpers\Icon;
use kartik\widgets\ActiveForm;
use dosamigos\fileupload\FileUploadUI;

/**
 * Youtube Uploader widget.
 *
 * @author Malcolm Paul <lefteyecc@ninjasitm.com>
 * @since 1.0
 */
class VideoUpload extends \yii\base\Widget
{
	public $user;
	public $step = 'start';
	public $model;
	public $metadataModel;
	public $authClient;
	public $uploadClient;
	public $authToken;
	public $authCode;
	public $afterUploadUrl;
	public $method = 'upload';
	
	protected $form;
	protected $uploadToken;
	protected $_state;
	
    /**
     * @var string action stage of the upload process
     * displayed.
     */
    public $action;
	
	public function init()
	{
		if(!($this->model instanceof File))
			throw new \yii\base\InvalidValueException(__CLASS__.'::'.__FUNCTION__." Couldn't fnd a file model!");
		if(!($this->metadataModel instanceof FileMetadata))
			throw new \yii\base\InvalidValueException(__CLASS__.'::'.__FUNCTION__." Couldn't fnd a file metadata model!");
		if(!($this->user instanceof \Yii::$app->user->identityClass))
			throw new \yii\base\InvalidValueException(__CLASS__.'::'.__FUNCTION__." Couldn't fnd aproper user model!");
		parent::init();
	}

	public function run()
	{
		ob_start();
		echo $this->getHeader();
		if(Response::getFormat() == 'modal')
			echo Html::tag('div', 'If you cancel before everghing is done then please start over by clicking the '.Html::tag('button', 'Close', ['class' => 'btn btn-default']).' button and then clicking the '.Html::tag('button', Icon::show('plus').' Add a YouTube Video', ['class' => 'btn btn-success']).' button again. You can only upload one video at a time.', ['class' =>  'text-info'])."<br>";
		$this->startForm();
		switch($this->step)
		{
			case 'start':
			echo $this->form->field($this->model, 'title');
			echo $this->form->field($this->metadataModel, 'value')->textarea()->label('Description');
    
			echo Html::activeHiddenInput($this->model, 'type', ['value' => 'video']);
			echo Html::activeHiddenInput($this->model, 'remote_id', ['value' => $this->user->getId()]);
			echo Html::activeHiddenInput($this->model, 'remote_type', ['value' => 'user-uploaded-video']);
			if(Response::getFormat() == 'html')
				echo $this->getButtons();
			break;
			
			case 'upload':
			//**Depending on the selected add method either show an  upload form or show a video search form for the PickledUp account.
			$this->getYouTubeUploadParams();
			echo Html::tag('h3', $this->model->title);
			echo Html::tag('p', $this->metadataModel->value);
			
			if($this->hasState('authenticated')) {
				
				echo Html::tag('h3', "Got token for upload. You can upload your video now", ['class' => 'text-success']);
				
			
				// Define an object that will be used to make all API requests.
				$youtube = new \Google_Service_YouTube($this->authClient);
				
				$url = $this->authClient->getAccessToken();
				print_r($url);
				exit;
				echo FileUploadUI::widget([
					'model' => $this->model,
					'attribute' => 'file_name',
					'url' => \Google_Service_YouTube::YOUTUBE_UPLOAD."?nextUrl=".$this->afterUploadUrl,
					'options' => [
						'accept' => 'video/*',
						'id' => 'youtube-video-import',
						'name' => 'file_name'
					],
					'authClientOptions' => [
						'limitMultipleFileUploads' => 2,
						'maxFileSize' => 200000000
					],
					// Also, you can specify jQuery-File-Upload events
					// see: https://github.com/blueimp/jQuery-File-Upload/wiki/Options#processing-callback-options
					'authClientEvents' => [
						'fileuploaddone' => 'function(e, data) {
							alert("Please reload the page and wait for the video to complete processing on youtube");
						}',
						'fileuploadfail' => 'function(e, data) {
							$([role="fileUploadMessage"]).html(data.message);
						}',
						'fileuploadadd' => 'function (e, data) {
							//Only submit if the form is validated properly
							var $activeForm = $("#'.$this->form->id.'").yiiActiveForm();
							$activeForm.yiiActiveForm("data").submitting = true; 
							$activeForm.yiiActiveForm("validate");
						}',
						'fileuploadsubmit' => 'function(e, data) {
							//Only submit if the form is validated properly
							var $activeForm = $("#'.$this->form->id.'").yiiActiveForm();
							
							data.context.find(":submit").prop("disabled", false);
							//Change the URL to the URL of the newly created import Source
							$(data.form).fileupload("option",
								"url",
								$activeForm.attr("action")
							);
							
							var validated = $activeForm.yiiActiveForm("data").validated;
							return validated && ($activeForm.data("id") != undefined);
						}'
					],
				]);
				echo Html::hiddenInput('token', $this->authToken);
			}
			else {
				$this->step = 'auth';
				echo Html::tag('h3', "Need to get the auth token from YouTube", ['class' => 'text-warning']);
			}
			if(Response::getFormat() == 'html')
				echo $this->getButtons();
            break;
			
			default:
			$form = '';
			break;
		}
		$this->endForm();
		$form = ob_get_contents();
		ob_end_clean();
		
		Response::initContext(\Yii::$app->controller);
		
		if(Response::getFormat() == 'html') {
			return $this->getTitle().$form;
		}
		else {
			Response::setFormat('modal');
			Response::viewOptions([
				'title' => $this->getTitle(),
				'footer' => $this->getButtons(),
				'args' => [
					'content' => $form
				]
			]);
			return $form;
		}
	}
	
	protected function getHeader()
	{
		switch($this->method)
		{
			case 'upload':
			$title = Html::tag('h2', 'Upload a new Youtube Video');
			break;
			
			default;
			$title = Html::tag('h2', 'Add a Youtube Video using a link');
			break;
		}
		return $title;
	}
	
	protected function getTitle()
	{
		switch($this->step)
		{
			case 'start':
			$title = Html::tag('h2', 'Step 1:  Add Video Metadata');
			break;
			
			case 'upload';
			$title = Html::tag('h2', 'Upload File');
			break;
			
			default:
			$title = Html::tag('h2', "Unknown step. How'd you get here?", ['class' => 'text-error']);
			break;
		}
		return $title;
	}
	
	protected function getButtons()
	{
		switch($this->step)
		{
			case 'start':
			$buttons = Html::submitButton(Yii::t('app', 'Next: Upload the video'), [
				'class' => 'btn btn-info',
				'form' => $this->form->options['id']
			]);
			break;
			
			case 'upload';
			$buttons = Html::submitButton(Yii::t('app', 'Next: Wait for Youtube.'), [
				'class' => 'btn btn-info',
				'form' => $this->form->options['id']
			]);
			break;
			
			default:
			$buttons = Html::tag('h4', "Error Unknown Step", ['class' => 'text-error']);
			break;
		}
		return $buttons;
	}
    
    protected function getYouTubeUploadParams()
    {
		// Taken from https://developers.google.com/youtube/2.0/developers_guide_php#Uploading_Videos
		// Note that this example creates an unversioned service object.
		// You do not need to specify a version number to upload content
		// since the upload behavior is the same for all API versions.
		
		if(strlen($this->authToken) && isset($this->authClient)) {
			$this->authClient->setAccessToken($this->authToken);
			$this->authClient->setScopes(\Google_Service_YouTube::YOUTUBE_UPLOAD);
			$this->uploadToken = $this->authClient->getAccessToken();
			print_r($this->authClient->execute(new \Google_Http_Request('https://gdata.youtube.com/action/GetUploadToken', 'post', [
				'Authorization' => json_decode($this->uploadToken)->access_token
			])));
			exit;
			$this->_state = 'authenticated';
			
			// create a new VideoEntry object
			/*$myVideoEntry = new \ZendGData\YouTube\VideoEntry();
			
			$myVideoEntry->setVideoTitle('My Test Movie');
			$myVideoEntry->setVideoDescription('My Test Movie');
			// The category must be a valid YouTube category!
			$myVideoEntry->setVideoCategory('Autos');
			
			// Set keywords. Please note that this must be a comma-separated string
			// and that individual keywords cannot contain whitespace
			$myVideoEntry->SetVideoTags('cars, funny');*/
		} else {
			Cache::cache()->set('youtube-upload-step-1-'.$this->user->getId(), \Yii::$app->request->post(), 300);
		}
    }
    
	protected function hasState($state)
	{
		return $this->_state === $state;
	}
	
    protected function startForm()
    {
		$this->form = ActiveForm::begin([
            'action' => $this->action,
			'options' => [
				'id' =>'nitm-youtube-uplload-form',
				'role' => 'ajaxForm youtubeUploadForm'
			],
            'enableAjaxValidation'   => true,
            'enableClientValidation' => true,
        ]);
    }
    
    protected function endForm()
    {
    	ActiveForm::end();
    }
}
