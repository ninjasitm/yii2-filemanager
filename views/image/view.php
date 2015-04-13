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
    <div id="existing-image">
    <?php 		
		if($model->getId())
		echo $this->render("thumbnail", [
			'model' => $model
		]);
		
		if(!$model->getIsNewRecord()) {
			echo $this->render("actions", [
				'model' => $model,
				'actions' => $actions
			]);
		}
    ?>
    </div>
    <div style="display:<?= $model->getIsNewRecord() ? "block" : "none" ?> " role="imageFile">
        <span id="progress" class="progress progress-striped active" style="display:none">
            <div id="bar" class="progress-bar" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width:0%">
                <span id="precent"></span>
            </div>
        </span>
	<?= FileInput::widget($pluginOptions) ?>
    </div>
</div>