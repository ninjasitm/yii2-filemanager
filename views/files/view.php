<?php
use yii\helpers\Html;
use kartik\widgets\ActiveForm;
use kartik\widgets\ActiveField;
use kartik\grid\GridView;
use nitm\helpers\Icon;
$modelDataProvider = new \yii\data\ArrayDataProvider([
	'allModels' => [$model]
]);

/**
 * @var yii\web\View $this
 * @var provisioning\models\ProvisioningFile $model
 */

$this->title = $model->file_name;
$this->params['breadcrumbs'][] = ['label' => 'Files', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
$dataProvider = isset($dataProvider) ? $dataProvider : new \yii\data\ArrayDataProvider(['allModels' => [$model]]);

?>
<?php 
	if(!\Yii::$app->request->isAjax && !isset($noBreadcrumbs))
		echo \yii\widgets\Breadcrumbs::widget(['links' => $this->params['breadcrumbs']]);
?>
<?= GridView::widget([
	'striped' => false,
	'responsive' => false, 
	'floatHeader'=> false,
	'options' => [
		"style" => "border-top:solid medium #CCC",
		"class" => \nitm\helpers\Statuses::getIndicator($model->getStatus()),
		'id' => 'file'.$model->getId(),
		'role' => 'statusIndicator'.$model->getId()
	],
	'dataProvider' => $dataProvider,
	'showFooter' => false,
	'summary' => false,
	'layout' => "{items}",
	"tableOptions" => [
		'class' => 'table table-responsive',
		'style' => 'background-color: transparent'
	],
	'columns' => [
		[
			'label' => 'ID',
			'attribute' => 'id',
			'format' => 'html',
			'value' => function ($model) {
				return Html::tag('strong', $model->getId(), ['style' => 'font-size: 18px']);
			},
			'options' => [
				'rowspan' => 3,
			],
			'contentOptions' => [
				'class' => 'text-center'
			],
			'headerOptions' => [
				'class' => 'text-center'
			]
		],
		[
			'format' => 'html',
			'attribute' => 'icon',
			'label' => 'Icon',
			'value' => function ($model) {
				return \nitm\filemanager\widgets\Thumbnail::widget([
					'size' => 'small',
					'model' => $model->icon(),
					'htmlIcon' => $model->html_icon,
				]);
			},
			'contentOptions' => [
				'class' => 'text-center'
			],
			'headerOptions' => [
				'class' => 'text-center col-md-1 col-lg-1 col-sm-2',
			]
		],
		[
			'format' => 'html',
			'attribute' => 'file_name',
			'label' => 'Name',
		],
		[
			'format' => 'html',
			'attribute' => 'type',
			'label' => 'Type',
		],
		[
			'format' => 'html',
			'attribute' => 'size',
			'label' => 'size',
			'value' => function ($model) {
				return $model->getSize();
			}
		],
		// 'resolved_on',
		// 'ccid',
		// 'ccnum',
		// 'ttnum',
		// 'address:ntext',
		// 'disabled_on',
		// 'notes:ntext',
		// 'edits',
		// 'edited',
		// 'editor',
		[
			'label' => 'Created On',
			'attribute' => 'created_at',
			'format' => 'datetime',
		],
		[
			'attribute' => 'author',
			'label' => 'Author',
			'format' => 'html',
			'value' => function ($model, $index, $widget) {
				return $model->author()->url(\Yii::$app->getModule('nitm')->useFullnames, \Yii::$app->request->url, [$model->formName().'[author]' => $model->author()->getId()]);
			}
		],

		[
			'class' => 'yii\grid\ActionColumn',
			'buttons' => [
				'form/update' => function ($url, $model) {
					return \nitm\widgets\modal\Modal::widget([
						'size' => 'large',
						'toggleButton' => [
							'tag' => 'a',
							'class' => 'fa-2x',
							'label' => Icon::forAction('update'), 
							'href' => \Yii::$app->urlManager->createUrl([$url, '__format' => 'modal']),
							'title' => Yii::t('yii', 'Edit '),
							'role' => 'dynamicAction updateAction disabledOnClose',
						],
						'dialogOptions' => [
							"class" => "modal-full"
						]
					]);
				},
				'view' => function ($url, $model) {
					return \nitm\widgets\modal\Modal::widget([
						'size' => 'normal',
						'toggleButton' => [
							'tag' => 'a',
							'class' => 'fa-2x',
							'label' => Icon::forAction('view'), 
							'href' => \Yii::$app->urlManager->createUrl([$url, '__format' => 'modal']),
							'title' => Yii::t('yii', 'View file'),
							'role' => 'metaAction viewAction disabledOnClose',
							'data-id' => 'view-template'.$model->getId()
						],
						'dialogOptions' => [
							"class" => "modal-full"
						]
					]);
				},
				'delete' => function ($url, $model) {
					return Html::a(Icon::forAction('delete'), \Yii::$app->urlManager->createUrl([$url, '__format' => 'json']), [
						'class' => 'fa-2x',
						'title' => \Yii::t('yii', 'Delete File'),
						'data-pjax' => '0',
						'role' => "deleteFile disableParent dynamicValue",
						'inline' => true,
						'data-type' => 'json',
						'data-parent' => 'file'.$model->getId(),
						'data-depth' => 0,
						'data-method' => 'post'
					]);
				},
				'download' => function ($url, $model) {
					return Html::a(Icon::forAction('download'), \Yii::$app->urlManager->createUrl([$url, '__format' => 'json']), [
						'class' => 'fa-2x',
						'title' => \Yii::t('yii', 'Download File'),
						'data-pjax' => '0',
						'inline' => true,
						'data-parent' => 'file'.$model->getId(),
						'data-method' => 'post'
					]);
				},
			],
			'template' => "{form/update} {view} {delete} {download}",
			'urlCreator' => function($action, $model, $key, $index) {
				return '/'.$model->isWhat().'s/'.$action.'/'.$model->getId();
			},
			'options' => [
				'rowspan' => 2
			],
			'headerOptions' => [
				'class' => 'text-center col-md-1 col-lg-1 col-sm-2',
			]
		],
	],
	'beforeRow' => function ($model, $key, $index, $grid) {
		switch(1)
		{
			case \Yii::$app->user->identity->lastActive() < strtotime($model->created_at):
			case \Yii::$app->user->identity->lastActive() < strtotime($model->updated_at):
			echo $this->context->activityWidget([
					'type' => 'create',
					'size' => 'large',
			]);
			break;
		}
	},
	'afterRow' => function ($model, $key, $index, $grid){
		/*$descriptionInfo = Html::tag('div', '', [
			'style' => 'display:none',
			'class' => 'well',
			'id' => 'view-template'.$model->getId()
		]);
		
		$metaInfo = Html::tag('div',
			Html::tag('div', $descriptionInfo, ['class' => 'col-md-12 col-lg-12']),
			['class' => 'row']
		);
		$activityInfo = '';*/
		
		
		if($model->getFileExists())
			$shortLink = \nitm\widgets\metadata\ShortLink::widget([
				'label' => 'Url',
				'url' => $model->getUrl(),
				'header' => $model->file_name,
				'type' => (\Yii::$app->request->isAjax ? 'page' : 'modal'),
				'size' => 'large'
			]).
			\nitm\widgets\metadata\ShortLink::widget([
				'label' => 'File Location',
				'url' => $model->getPath(),
				'header' => $model->file_name,
				'type' => (\Yii::$app->request->isAjax ? 'page' : 'modal'),
				'size' => 'large'
			]);
		else
			$shortLink = Html::tag('h4', "No file found");
		return Html::tag('tr',
			Html::tag(
				'td', 
				$shortLink, 
				[
					'colspan' => 10, 
				]
			)
		);
	}
]); 
?>
