<?php

use yii\grid\GridView;
use dosamigos\fileupload\FileUploadUI;
use yii\widgets\LinkPager;
use kartik\widgets\ActiveForm;
use yii\widgets\ListView;
use nitm\filemanager\helpers\Html;
use nitm\filemanager\widgets\Thumbnail;


/* @var $this yii\web\View */
/* @var $searchModel linchpinstudios\filemanager\models\FileSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'File';
$this->params['breadcrumbs'][] = $this->title;

$awsConfig = \Yii::$app->getModule('nitm-files')->getEngine('aws');

if(isset($awsConfig['enable']) && $awsConfig['enable']){
    $path = $awsConfig['url'];
}else{
    $path = '/';
}

?>
<br>
<div class="filemanager-default-index <?= \Yii::$app->request->isAjax ? '' : 'col-sm-12'; ?>">
	<?php
		if(!isset($noBreadcrumbs) ||
			(isset($noBreadcrumbs) && !$noBreadcrumbs))
			echo \yii\widgets\Breadcrumbs::widget(['links' => $this->params['breadcrumbs']]);
	?>
    <div class="panel panel-default">
        <!--<div class="panel-heading">
			<div class="row">
				<div class="col-sm-12">
					<?php
						$form = ActiveForm::begin([
							'id' => 'file-search-form',
							'method' => 'get',
							'options' => [
								'class' => 'navbar-form'
							],
						]);
					?>
					<?= $form->field($searchModel, 'title', [
						'addon' => [
							'append' => [
								'content' => Html::submitButton('<i class="glyphicon glyphicon-search"></i>', ['class' => 'btn btn-primary']),
							'asButton' => true
							]
						]
					])
					->textInput(['class' => 'form-control', 'placeholder' => 'Search'])
					->label('Search', ['class' => 'sr-only']); ?>
					<?php ActiveForm::end(); ?>
				</div>
			</div>
        </div> -->
        <div class="panel-body">
			<div class="upload-files" id="filemanagerUpload" style='display:block'>
				<?= \nitm\filemanager\widgets\FileUpload::widget([
						'model' => $model instanceof \nitm\filemanager\models\File ? $model : $model->file(),
					]); ?>
			</div>
            <div class="display-files" id="fileGridManager">
				<?=
					$this->render('view', [
						'options' => [
							'id' => 'files'
						],
						'dataProvider' => $dataProvider,
						'noBreadcrumbs' => true
					]);
				?>
            </div>
        </div>
        <div class="panel-footer" id="fileGridFooter">
            <?= linkPager::widget([
                    'pagination' => $dataProvider->pagination,
                ]);
			?>
        </div>
    </div>

</div>
