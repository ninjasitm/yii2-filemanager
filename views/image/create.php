<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model linchpinstudios\filemanager\models\Files */

$this->title = 'Add Images';
$this->params['breadcrumbs'][] = ['label' => 'Files', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="files-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('forms/_form', [
        'model' => $model,
    ]) ?>

</div>
