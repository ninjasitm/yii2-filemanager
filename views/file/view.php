<?php
use yii\helpers\Html;
use kartik\widgets\ActiveForm;
use kartik\widgets\ActiveField;
use kartik\grid\GridView;
use nitm\helpers\Icon;

/**
 * @var yii\web\View $this
 * @var provisioning\models\ProvisioningFile $model
 */

if(isset($model)) {
	$this->title = $model->file_name;
	$dataProvider = new \yii\data\ArrayDataProvider(['allModels' => [$model]]);
	$this->params['breadcrumbs'][] = ['label' => 'Files', 'url' => ['index']];
} else {
	$this->title = 'Files';
}

?>
<?php
	if(!isset($noBreadcrumbs) ||
		(isset($noBreadcrumbs) && !$noBreadcrumbs))
		echo \yii\widgets\Breadcrumbs::widget(['links' => $this->params['breadcrumbs']]);
?>
<?= GridView::widget([
	'striped' => false,
	'responsive' => true,
	'rowOptions' => function ($model) {
		return [
			"style" => "border-top:solid medium #CCC",
			"class" => \nitm\helpers\Statuses::getIndicator($model->getStatus()).' '.($model->is_default ? 'default' : ''),
			'id' => 'file'.$model->getId(),
			'role' => 'statusIndicator'.$model->getId()
		];
	},
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
			'attribute' => 'file_name',
			'label' => 'Name',
			'value' => function ($model) {
				return Html::tag('strong', $model->file_name);
			}
		],
		[
			'format' => 'html',
			'attribute' => 'size',
			'label' => 'Size',
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
			'class' => 'yii\grid\ActionColumn',
			'buttons' => [
				'delete' => function ($url, $model) {
					return Html::a(Icon::forAction('delete'), '#', [
						'class' => 'fa-2x',
						'title' => \Yii::t('yii', 'Delete File'),
						'data-pjax' => '0',
						'role' => "deleteAction deleteFile metaAction",
						'data-parent' => '#file'.$model->getId(),
						'data-method' => 'post',
						'data-action' => 'delete',
						'data-url' => \Yii::$app->urlManager->createUrl([$url, '__format' => 'json'])
					]);
				},
				'info' => function ($url, $model) {
					return Html::a(Icon::forAction('info'), '#', [
						'class' => 'fa-2x',
						'title' => \Yii::t('yii', 'Show more Information'),
						'data-pjax' => '0',
						'role' => "visibility",
						'data-id' => 'file-info'.$model->getId(),
					]);
				},
				'get' => function ($url, $model) {
					return Html::a(Icon::forAction('download'), \Yii::$app->urlManager->createUrl([$url, '__format' => 'json']), [
						'class' => 'fa-2x',
						'title' => \Yii::t('yii', 'Download File'),
						'data-pjax' => '0',
						'inline' => true,
						'data-parent' => 'file'.$model->getId(),
						'data-method' => 'get',
						'_target' => 'new'
					]);
				},
			],
			'template' => "{delete} {get} {info}",
			'urlCreator' => function($action, $model, $key, $index) {
				return '/'.$model->isWhat(true).'/'.$action.'/'.$model->getId();
			},
			'options' => [
				'rowspan' => 2,
				'class' => 'col-sm-3'
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
			return \nitm\widgets\activityIndicator\ActivityIndicator::widget([
					'type' => 'create',
					'size' => 'large',
			]);
			break;
		}
	},
	'afterRow' => function ($model, $key, $index, $grid) {

		$metaInfo = \nitm\widgets\metadata\StatusInfo::widget([
			'items' => [
				[
					'blamable' => $model->author(),
					'date' => $model->created_at,
					'value' => $model->created_at,
					'label' => [
						'true' => "Created On ",
					]
				],
				[
					'value' => $model->type,
					'label' => [
						'true' => "File type ",
					]
				],
				[
					'value' => $model->getSize(),
					'label' => [
						'true' => "File size ",
					]
				],
			]
		]);

		if($model->getFileExists()) {
			$shortLink = \nitm\widgets\metadata\ShortLink::widget([
				'label' => 'Url',
				'url' => $model->url(),
				'header' => $model->file_name,
				'type' => (\Yii::$app->request->isAjax ? 'page' : 'modal'),
				'size' => 'large'
			]).
			\nitm\widgets\metadata\ShortLink::widget([
				'label' => 'Path',
				'url' => $model->getRealPath(),
				'header' => $model->file_name,
				'type' => (\Yii::$app->request->isAjax ? 'page' : 'modal'),
				'size' => 'large'
			]);
		} else
			$shortLink = Html::tag('h4', "No file found");
		return Html::tag('tr',
			Html::tag('td', $metaInfo.$shortLink, [
				'colspan' => 10,
			]), [
			'class' => 'hidden',
			'id' => 'file-info'.$model->getId()
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
