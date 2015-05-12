<?php

use yii\helpers\Html;
use kartik\widgets\FileInput;
use kartik\icons\Icon;
use pickledup\models\Image;
use pickledup\helpers\Storage;
use nitm\helpers\Helper;
use nitm\helpers\Icon as NitmIcon;

/**
 * @var yii\web\View $this
 * @var pickledup\models\Instructions $model
 * @var yii\widgets\ActiveForm $form
 */
 
$action = $model->getIsNewRecord() ? 'create' : 'update';
$type = $model->is_default ? 'default' : 'extra';
?>

<div <?= Helper::splitc(array_keys($wrapperOptions), array_values($wrapperOptions), '=', ' ', false, false) ?>>
    <?php 		
		if($model->getId())
		echo $this->render("thumbnail", [
			'model' => $model,
			'options' => $wrapperOptions
		]);
		
		if(!$model->getIsNewRecord()) {
			echo $this->render("actions", [
				'model' => $model,
				'actions' => $actions,
				'options' => $wrapperOptions
			]);
		}
    ?>
</div>