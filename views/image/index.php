<?php

use yii\grid\GridView;
use dosamigos\fileupload\FileUploadUI;
use yii\widgets\LinkPager;
use kartik\widgets\ActiveForm;
use yii\widgets\ListView;
use nitm\filemanager\helpers\Html;
use nitm\filemanager\widgets\Thumbnail;


/* @var $this yii\web\View */
/* @var $searchModel linchpinstudios\filemanager\models\ImageSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

if(!isset($this->title))
	$this->title = 'Image';
$this->params['breadcrumbs'][] = $this->title;

$awsConfig = \Yii::$app->getModule('nitm-files')->getEngine('aws');

if(isset($awsConfig['enable']) && $awsConfig['enable']){
    $path = $awsConfig['url'];
}else{
    $path = '/';
}

?>
<br>
<div class="image-manager-default-index <?= \Yii::$app->request->isAjax ? '' : 'col-sm-12'; ?>">
	<?php
		if(!isset($noBreadcrumbs) ||
			(isset($noBreadcrumbs) && !$noBreadcrumbs))
			echo \yii\widgets\Breadcrumbs::widget(['links' => $this->params['breadcrumbs']]);
	?>
    <div class="panel panel-default">
        <!-- <div class="panel-heading">
			<div class="row">
				<div class="col-sm-12">
					<?php
						$form = ActiveForm::begin([
							'id' => 'image-search-form',
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
			<div class="upload-images" id="filemanagerUpload" style="display:block">
				<?= \nitm\filemanager\widgets\ImageUpload::widget([
						'model' => $model instanceof \nitm\filemanager\models\Image ? $model : $model->image(),
					]); ?>
			</div>
            <div class="display-images" id="imageGridManager">
				<?=
					$this->render('data', [
						'options' => [
							'id' => 'images',
							'role' => 'imagesContainer'
						],
						'dataProvider' => $dataProvider,
						'noBreadcrumbs' => true
					]);
				?>
            </div>
        </div>
        <div class="panel-footer" id="imageGridFooter">
            <?= linkPager::widget([
                    'pagination' => $dataProvider->pagination,
                ]);
			?>
        </div>
    </div>

</div>
