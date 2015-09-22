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
    <div class="panel panel-default">
        <div class="panel-heading">
			<div class="row">
				<div class="col-md-6 col-lg-6">
					<?= Html::a(Html::tag('i','',['class' => 'glyphicon glyphicon-th-large']), '', ['data-toggle' => 'modal', 'class' => 'btn btn-primary navbar-btn disabled', 'id' => 'fileGridBtn']); ?>
					<?= Html::a(Html::tag('i','',['class' => 'glyphicon glyphicon-cloud-upload']), '', ['class' => 'btn btn-success navbar-btn', 'data-toggle' => 'modal', 'id' => 'fileUploadBtn']); ?>
				</div>
				<div class="col-md-6 col-lg-6">
					<?php
						$form = ActiveForm::begin([
							'id' => 'file-search-form',
							'method' => 'get',
							'options' => [
								'class' => 'navbar-form navbar-right'
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
        </div>
        <div class="panel-body">
            <div class="display-images" id="fileGridManager">
				<?=
					$this->render('view', [
						'options' => [
							'id' => 'files'
						],
						'dataProvider' => $dataProvider,
						'noBreadcrumbs' => isset($noBreadcrumbs) ? $noBreadcrumbs : false
					]);
				?>
            </div>
            <div class="upload-images" id="filemanagerUpload">
                <?= FileUploadUI::widget([
                    'model' => $model,
                    'attribute' => 'file_name',
                    'url' => '/files/save/'.$type.'/'.$id,
                    'options' => [
                        'done'   => 'filemanager',
						//'enctype' => 'multipart/form-data'
                    ],
                    'clientOptions' => [
                        'maxFileSize' => 2000000,
                    ]
                ]);?>
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

<div class="modal fade" id="editProperties" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">

            <div class="modal-body"></div>
            
        </div>
    </div>
</div>


