<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use dosamigos\fileupload\FileUploadUI;
use nitm\filemanager\models\Image;

/* @var $this yii\web\View */
/* @var $model linchpinstudios\filemanager\models\Files */
/* @var $form yii\widgets\ActiveForm */

?>

<div class="files-form">
<?= FileUploadUI::widget([
		'model' => $model,
		'attribute' => 'images[]',
		'url' => '/image/save/'.$model->isWhat().'/'.$model->getId(),
		'options' => [
			'accept' => 'image/*',
			'id' => 'image-upload',
			'name' => 'images[]'
		],
		'clientOptions' => [
			'limitMultipleFileUploads' => 2,
			'maxFileSize' => 200000000
		],
		// Also, you can specify jQuery-File-Upload events
		// see: https://github.com/blueimp/jQuery-File-Upload/wiki/Options#processing-callback-options
		'clientEvents' => [
			'fileuploaddone' => 'function(e, data) {
			}',
			'fileuploadfail' => 'function(e, data) {
				$([role="fileUploadMessage"]).html(data.message);
			}',
			'fileuploadadd' => 'function (e, data) {
				//Only submit if the form is validated properly
			}',
			'fileuploadsubmit' => 'function(e, data) {
			}'
		],
	]);
?>
</div>
