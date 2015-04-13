<?php

use yii\helpers\Html;
use kartik\widgets\FileInput;
use kartik\icons\Icon;
use pickledup\models\Image;
use pickledup\helpers\Storage;
use nitm\helpers\Helper;
use nitm\helpers\Icon as NitmIcon;

/**
 * @var yii\web\View $this
 * @var pickledup\models\Instructions $model
 * @var yii\widgets\ActiveForm $form
 */
 
$action = $model->getIsNewRecord() ? 'create' : 'update';
$type = $model->isDefault() ? 'default' : 'extra';
$placeholder = isset($placeholder) ? $placeholder : false;
?>
<?php 
    $thumbnail = $frame = $status = $clear = $close = $preview = '';
    switch($placeholder)
    {
        case true:
        $img = Html::tag('div', Image::getPlaceHolder(), [
            'class' => 'existing-image '.($model->isDefault() ? 'file-preview-image' : 'file-preview-image-sm'),
        ]);
        break;
        
        default:
        switch($model instanceof Image)
        {
            case false:
            $preview = Html::tag('div', '', [
                'class' => ($model->isDefault() ? 'file-preview-image' : 'file-preview-image-sm'),
                //'id' => ($model->isDefault() ? 'default-image' : 'extra-image'.$model->id)
            ]);
            break;
            
            default:
            $icon = $model->getIcon();
            $img = Html::img('/image/get/'.$icon->id.'/medium?__format=raw', [
                'class' => ($model->isDefault() ? 'file-preview-image-sm' : 'file-preview-image-sm'),
                'title' => $type."-image-".$icon->id,
            ]);
            $frame = Html::tag('div', $img, [
                'class' => ($model->isDefault() ? 'file-preview-frame-sm' : 'file-preview-frame-sm'),
            ]);
            $thumbnail = Html::tag('div', $frame, ['class' => 'file-preview-thumbnails']);
            $status = Html::tag('div', '', ['class' => 'file-preview-status text-center text-success']);
            $clear = Html::tag('div', '', ['class' => 'clearfix']);
            $preview = Html::tag('div', 
                $status.$thumbnail.$clear,
                [
                    'class' => ($model->isDefault() ? 'file-preview' : 'file-preview-sm'),
                    //'id' => ($model->isDefault() ? 'default-image' : 'extra-image'.$model->id),
                ]
            );
			if(isset($withActions))
			{
				$preview .= $this->render("actions", [
					'model' => $model,
					'actions' => $actions
				]);
			}
            break;
        }
        break;
    }
    echo $preview;
?>