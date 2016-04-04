<?php
use yii\helpers\Html;
use yii\bootstrap\ButtonGroup;
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

$this->title = $model->file_name;

if(!isset($noBreadcrumbs) || (isset($noBreadcrumbs) && !$noBreadcrumbs))
	echo \yii\widgets\Breadcrumbs::widget(['links' => $this->params['breadcrumbs']]);

if(!is_callable('getUrl')) {
	function getUrl($action, $model) {
		return '/'.$model->isWhat().'/'.$action.'/'.$model->getId();
	}
}
?>

<?php
	echo Html::beginTag('div', [
		"class" => 'well well-sm row media',
		'role' => 'statusIndicator'.$model->getId().' imageContainer '.($model->isDefault() ? 'defaultImage' : 'extraImage'),
		'id' => 'image'.$model->getId()
	]);
?>

	<div class="col-md-1 col-lg-1 col-sm-2 text-center">
		<span class="media-middle">
			<?php
				if(\Yii::$app->user->identity->lastActive() < strtotime($model->created_at)
					|| \Yii::$app->user->identity->lastActive() < strtotime($model->updated_at))
					echo  \nitm\widgets\activityIndicator\ActivityIndicator::widget([
							'type' => 'create',
							'size' => 'large',
					]);
			?>
			<?= Html::tag('strong', $model->getId(), ['style' => 'font-size: 24px']) ?>
		</span>
	</div>
	<div class="col-md-2 col-lg-2 col-sm-4 text-center">
		<span class="media-middle">
			<?= Html::a($model->icon->getIconHtml('small', [
				'class' => 'thumbnail thumbnail-lg media-object '.($model->isDefault() ? 'default' : ''),
				'url' => $model->url('small')
			]), $model->url('small')) ?>
		</span>
	</div>
	<div class="col-md-3 col-lg-3 visible-lg text-center">
		<span class="media-middle">
			<?= Html::tag('strong', $model->file_name) ?>
		</span>
	</div>
	<div class="col-md-2 col-lg-2 col-sm-2 visible-lg text-center">
		<span class="media-middle">
			<?= $model->getSize(); ?>
		</span>
	</div>
	<div class="col-md-4 col-lg-4 col-sm-4 text-center">
		<span class="media-middle">
			<?= ButtonGroup::widget([
				'encodeLabels' => false,
				'buttons' => [
					'delete' => [
						'tagName' => 'a',
						'label' => Icon::forAction('delete').Html::tag('span', ' Delete', [
							'class' => 'hidden-sm'
						]),
						'options' => [
							'class' => 'btn btn-danger',
							'title' => \Yii::t('yii', 'Delete Image'),
							'data-pjax' => '0',
							'role' => "deleteAction deleteImage metaAction",
							'data-parent' => '#image'.$model->getId(),
							'data-method' => 'post',
							'data-action' => 'delete',
							'data-url' => \Yii::$app->urlManager->createUrl([getUrl('delete', $model), '__format' => 'json'])
						]
					],
					'info' => [
						'label' => Icon::forAction('info').Html::tag('span', ' Info', [
							'class' => 'hidden-sm'
						]),
						'options' => [
							'class' => 'btn btn-info',
							'title' => \Yii::t('yii', 'Show more Information'),
							'data-pjax' => '0',
							'role' => "visibility",
							'data-id' => 'image-info'.$model->getId(),
						]
					],
					'default' => [
						'tagName' => 'a',
						'label' => Icon::forAction('thumb-tack').Html::tag('span', ' Default', [
							'class' => 'hidden-sm'
						]),
						'options' => [
							'class' => 'btn btn-success '.($model->isDefault() ? 'hidden' : ''),
							'title' => \Yii::t('yii', 'Set this image as default'),
							'data-pjax' => '0',
							'role' => "toggleDefaultImage",
							'data-id' => 'image-default'.$model->getId(),
							'data-parent' => 'image'.$model->getId(),
							'href' => getUrl('default', $model)
						]
					],
					'get' => [
						'tagName' => 'a',
						'label' => Icon::forAction('download').Html::tag('span', ' Download', [
							'class' => 'hidden-sm'
						]),
						'options' => [
							'class' => 'btn btn-default',
							'title' => \Yii::t('yii', 'Download Image'),
							'data-pjax' => '0',
							'inline' => true,
							'data-parent' => 'image'.$model->getId(),
							'data-method' => 'get',
							'_target' => 'new',
							'href' => $model->url()
						]
					],
				],
			]); ?>
		</span>
	</div>
	<div class="col-sm-12 hidden" id='image-info<?=$model->getId();?>'>
		<h2>Metadata Information</h2>
		<div class="well">
		<?php
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
		echo Html::tag('tr',
			Html::tag('td', $metaInfo.$shortLink, [
				'colspan' => 10,
			]), [
			'class' => 'hidden',
			'id' => 'image-info'.$model->getId()
		]); ?>
		</div>
	</div>
<?= Html::endTag('div') ?>
