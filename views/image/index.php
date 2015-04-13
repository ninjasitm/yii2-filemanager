<?php

use yii\helpers\Html;
use yii\grid\GridView;

/**
 * @var yii\web\View $this
 * @var yii\data\ActiveDataProvider $dataProvider
 * @var pickledup\models\search\Instructions $searchModel
 */

$this->title = Yii::t('app', 'Instructions');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="instructions-index">

    <h1><?= Html::encode($this->title) ?></h1>
    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <p>
        <?= Html::a(Yii::t('app', 'Create {modelClass}', [
    'modelClass' => 'Instructions',
]), ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            'id',
            [
				'attribute' => 'priority',
				'format' => 'html',
				'value' => function ($model) {
					return Html::tag('strong', $model->priority, ['class' => 'text-center']);
				}
			],
            [
				'attribute' => 'drink_id',
				'value' => function ($model) {
					return $model->drink->title;
				}
			],
            [
				'attribute' => 'author_id',
				'value' => function ($model) {
					return $model->author->fullName(true);
				}
			],
            [
				'attribute' => 'action_id',
				'value' => function ($model) {
					return $model->action->name;
				}
			],
            [
				'attribute' => 'quantity',
				'value' => function ($model) {
					return @$model->quantity;
				}
			],
            [
				'attribute' => 'unit_id',
				'value' => function ($model) {
					return $model->unit->name;
				}
			],
            'ingredient_id',
            // 'created_at',
            // 'updated_at',
            'deleted:boolean',

            ['class' => 'yii\grid\ActionColumn'],
        ],
    ]); ?>

</div>
