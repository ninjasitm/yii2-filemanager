<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use dosamigos\fileupload\FileUploadUI;
use nitm\filemanager\models\Image;

/* @var $this yii\web\View */
/* @var $model linchpinstudios\filemanager\models\Files */
/* @var $form yii\widgets\ActiveForm */

?>
<div class="images-form">
<?= \nitm\filemanager\widgets\ImageUpload::widget([
		'model' => $model->image(),
	]);
?>
</div>
