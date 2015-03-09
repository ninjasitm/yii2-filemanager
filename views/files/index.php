<?php

use yii\grid\GridView;
use dosamigos\fileupload\FileUploadUI;
use yii\widgets\LinkPager;
use yii\widgets\ActiveForm;
use yii\widgets\ListView;
use nitm\filemanager\helpers\Html;
use nitm\filemanager\widgets\Thumbnail;


/* @var $this yii\web\View */
/* @var $searchModel linchpinstudios\filemanager\models\FileSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'File';
$this->params['breadcrumbs'][] = $this->title;

$awsConfig = $this->context->module->aws;

if($awsConfig['enable']){
    $path = $awsConfig['url'];
}else{
    $path = '/';
}

?>

<div class="filemanager-default-index col-lg-12 col-md-12">

    <div class="panel panel-default">
        <div class="panel-heading">
            <div class="pull-left">
                <?= Html::a(Html::tag('i','',['class' => 'glyphicon glyphicon-th-large']), '', ['data-toggle' => 'modal', 'class' => 'btn btn-primary navbar-btn disabled', 'id' => 'fileGridBtn']); ?>
                <?= Html::a(Html::tag('i','',['class' => 'glyphicon glyphicon-cloud-upload']), '', ['class' => 'btn btn-success navbar-btn', 'data-toggle' => 'modal', 'id' => 'fileUploadBtn']); ?>
            </div>
            
            <?php
            $form = ActiveForm::begin([
                'id' => 'file-search-form',
                'method' => 'get',
                'options' => [
					'class' => 'navbar-form navbar-right'
				],
            ]);
                echo Html::beginTag('div',['class' => 'form-group']);
                    echo $form->field($searchModel, 'title')->textInput(['class' => 'form-control', 'placeholder' => 'Search']);
                echo Html::endTag('div');
                    echo Html::submitButton('<i class="glyphicon glyphicon-search"></i>', ['class' => 'btn btn-primary']);
            ActiveForm::end();
            ?>
            
            <!--<form class="navbar-form navbar-right" role="search">
                <div class="form-group">
                    <input type="text" class="form-control" placeholder="Search">
                </div>
                <button type="submit" class="btn btn-default"><i class="glyphicon glyphicon-search"></i></button>
            </form> -->
            <div class="clearfix"></div>
        </div>
        <div class="panel-body">
            <div class="display-images" id="fileGridManager">
                <div class="row">
                    <?=
						ListView::widget([
							'options' => [
								'id' => 'files'
							],
							'dataProvider' => $dataProvider,
							'itemOptions' => [
								'class' => 'item'
							],
							'itemView' => function($model, $key, $index, $widget) {
								return $this->render('view', [
									'index' => $index,
									'model' => $model,
									'noBreadcrumbs' => true
								]);
							},
							'pager' => [
								'class' => \nitm\widgets\ias\ScrollPager::className(),
								'overflowContainer' => '#files-ias-container',
								'container' => '#files',
								'item' => ".item",
								'negativeMargin' => 150,
								'delay' => 500,
							]
						]);
                    ?>
                </div>
            </div>
            <div class="upload-images" id="filemanagerUpload">
                <?= FileUploadUI::widget([
                    'model' => $model,
                    'attribute' => 'file_name',
                    'url' => ['/files/upload'],
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
            <?php
                
                echo linkPager::widget([
                    'pagination'=>$dataProvider->pagination,
                ]);
            
            ?>
        </div>
    </div>
    
</div>


<script>
    
    
    
</script>



<div class="modal fade" id="editProperties" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">

            <div class="modal-body"></div>
            
        </div>
    </div>
</div>


