<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model pendalf89\filemanager\models\Mediafile */
/* @var $form yii\widgets\ActiveForm */

?>

<?= $model->getIconHtml() ?>

<ul class="detail">
    <li><?= $model->type ?></li>
    <li><?= Yii::$app->formatter->asDatetime($model->getLastChanges()) ?></li>
    <li><?= $model->getFileSize() ?></li>
    <li><?= Html::a(Module::t('main', 'Delete'), ['image/delete/'.$model->getId()],
            [
                'class' => 'text-danger',
                'data-confirm' => Yii::t('yii', 'Are you sure you want to delete this image?'),
                'data-id' => $model->id,
                'role' => 'delete',
            ]
        ) ?></li>
</ul>

<div class="file_name"><?= $model->file_name ?></div>

<?php $form = ActiveForm::begin([
    'action' => ['image/update/'.$model->getId()],
    'options' => ['id' => 'control-form'],
]); ?>
    <?= $form->field($model, 'slug')->textInput(['class' => 'form-control input-sm']); ?>

    <?= $form->field($model, 'metadata[description]')->textarea(['class' => 'form-control input-sm']); ?>
    
	<?= Html::hiddenInput('url', $model->url) ?>

    <?= Html::hiddenInput('id', $model->getId()) ?>

    <?= Html::button(Module::t('main', 'Insert'), ['id' => 'insert-btn', 'class' => 'btn btn-primary btn-sm']) ?>

    <?= Html::submitButton(Module::t('main', 'Save'), ['class' => 'btn btn-success btn-sm']) ?>
<?php ActiveForm::end(); ?>