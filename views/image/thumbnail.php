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
            'class' => 'image '.($model->isDefault() ? 'default' : ''),
        ]);
        break;
        
        default:
        switch($model instanceof \nitm\filemanager\models\Image)
        {
            case false:
            $preview = Html::tag('div', '', [
                'class' => 'image '.($model->isDefault() ? 'default' : ''),
                //'id' => ($model->isDefault() ? 'default-image' : 'extra-image'.$model->id)
            ]);
            break;
            
            default:
            $icon = $model->getIcon('medium');
            $img = Html::a(Html::img($icon->url, [
                'title' => $type."-image-".$icon->getId(),
            ]), $model->url);
            $preview = Html::tag('div', 
                $img,
                [
               	 	'class' => 'thumbnail '.($model->isDefault() ? 'default' : 'extra')
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