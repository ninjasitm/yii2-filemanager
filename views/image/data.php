<?php
use yii\helpers\Html;
use kartik\widgets\ActiveForm;
use kartik\widgets\ActiveField;
use kartik\grid\GridView;
use nitm\helpers\Icon;

/**
 * @var yii\web\View $this
 * @var provisioning\models\ProvisioningImage $model
 */

$options = isset($options) ? $options : [
	'id' => 'images',
	'role' => 'imagesContainer'
];

if(isset($model)) {
	$this->title = $model->file_name;
	$dataProvider = new \yii\data\ArrayDataProvider(['allModels' => [$model]]);
	$this->params['breadcrumbs'][] = ['label' => 'Images', 'url' => ['index']];
} else {
	$this->title = 'Images';
}
?>
<?php
	if(!isset($noBreadcrumbs) ||
		(isset($noBreadcrumbs) && !$noBreadcrumbs))
		echo \yii\widgets\Breadcrumbs::widget(['links' => $this->params['breadcrumbs']]);
?>
<?= GridView::widget([
	'export' => false,
	'striped' => false,
	'responsive' => true,
	'options' => $options,
	'rowOptions' => function ($model) {
		return [
			"style" => "border-top:solid medium #CCC",
			"class" => \nitm\helpers\Statuses::getIndicator($model->getStatus()),
			'role' => 'statusIndicator'.$model->getId().' imageContainer '.($model->isDefault() ? 'defaultImage' : 'extraImage'),
			'id' => 'image'.$model->getId(),
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
			'format' => 'raw',
			'attribute' => 'icon',
			'label' => '',
			'value' => function ($model) {
				return Html::a(\nitm\filemanager\widgets\Thumbnail::widget([
					"model" => $model,
					"size" => "medium",
					"options" => [
						"class" => "thumbnail text-center ".($model->isDefault() ? 'default' : ''),
					]
				]), $model->url());
			}
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
		[
			'class' => 'yii\grid\ActionColumn',
			'buttons' => [
				'delete' => function ($url, $model) {
					return Html::a(Icon::forAction('delete'), '#', [
						'class' => 'fa-2x',
						'title' => \Yii::t('yii', 'Delete Image'),
						'data-pjax' => '0',
						'role' => "deleteAction deleteImage metaAction",
						'data-parent' => '#image'.$model->getId(),
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
						'data-id' => 'image-info'.$model->getId(),
					]);
				},
				'default' => function ($url, $model) {
					return Html::a(Icon::forAction('thumb-tack'), $url, [
						'class' => 'fa-2x '.($model->isDefault() ? 'hidden' : ''),
						'title' => \Yii::t('yii', 'Set this image as default'),
						'data-pjax' => '0',
						'role' => "toggleDefaultImage",
						'data-id' => 'image-default'.$model->getId(),
						'data-parent' => 'image'.$model->getId()
					]);
				},
				'get' => function ($url, $model) {
					return Html::a(Icon::forAction('download'), $model->url(), [
						'class' => 'fa-2x',
						'title' => \Yii::t('yii', 'Download Image'),
						'data-pjax' => '0',
						'inline' => true,
						'data-parent' => 'image'.$model->getId(),
						'data-method' => 'get',
						'_target' => 'new'
					]);
				},
			],
			'template' => "{delete} {get} {default} {info}",
			'urlCreator' => function($action, $model, $key, $index) {
				return '/'.$model->isWhat().'/'.$action.'/'.$model->getId();
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
						'true' => "Image type ",
					]
				],
				[
					'value' => $model->getSize(),
					'label' => [
						'true' => "Image size ",
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
			$shortLink = Html::tag('h4', "No image found");
		return Html::tag('tr',
			Html::tag('td', $metaInfo.$shortLink, [
				'colspan' => 10,
			]), [
			'class' => 'hidden',
			'id' => 'image-info'.$model->getId()
		]);
	},
	'pager' => [
		'class' => \nitm\widgets\ias\ScrollPager::className(),
		'overflowContainer' => '#images-ias-container',
		'container' => '#images',
		'item' => ".item",
		'negativeMargin' => 150,
		'delay' => 500,
	]
]);
?>
