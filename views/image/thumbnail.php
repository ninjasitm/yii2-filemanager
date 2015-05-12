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
        switch($model instanceof \nitm\filemanager\models\Image)
        {
            case false:
            $preview = Html::tag('div', '', [
                'class' => ($model->isDefault() ? 'file-preview-image' : 'file-preview-image-sm'),
                //'id' => ($model->isDefault() ? 'default-image' : 'extra-image'.$model->id)
            ]);
            break;
            
            default:
            $icon = $model->getIcon('medium');
            $img = Html::a(Html::img($icon->url, [
                'class' => 'image '.($model->isDefault() ? 'default' : 'extra'),
                'title' => $type."-image-".$icon->getId(),
            ]), $model->url);
            $thumbnail = Html::tag('div', $img, ['class' => 'file-preview-thumbnails']);
            $preview = Html::tag('div', 
                $thumbnail.$clear,
                [
                    'class' => 'file-preview '.($model->isDefault() ? 'default' : ''),
                    //'id' => ($model->isDefault() ? 'default-image' : 'extra-image'.$model->id),
                ]
            );
			if(isset($withActions))
			{
				$preview .= $this->render("actions", [
					'model' => $model,
					'actions' => $actions,
					'options' => $options
				]);
			}
            break;
        }
        break;
    }
    echo $preview;
?>