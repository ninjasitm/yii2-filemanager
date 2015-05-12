<?php

use yii\helpers\Html;
use nitm\helpers\Icon as NitmIcon;

/**
 * @var yii\web\View $this
 * @var pickledup\models\Instructions $model
 * @var yii\widgets\ActiveForm $form
 */
		
$modelActions = '';
foreach($actions as $name=>$action)
{
	switch(isset($action['adminOnly']) && ($action['adminOnly'] == true))
	{
		case true:
		switch(\Yii::$app->userMeta->isAdmin())
		{
			case true:
			$action['options']['id'] = $action['options']['id'].$model->id;
			$modelActions .= Html::a(
				Html::tag(
					$action['tag'], 
					Icon::show(
						$action['text']
					)
				), 
				$action['action'].'/'.$model->id, $action['options']
			);
			break;
		}
		break;
		
		default:
		if(($name == 'default') && $model->isDefault())
		{
			$action['options']['class'] .= ' hidden';
		}
		$action['options']['id'] = $action['options']['id'].$model->id;
		$action['options']['data-parent'] = '#'.$options['id'];
		$action['options']['data-id'] = $model->id;
		$modelActions .= Html::a(
			Html::tag(
				$action['tag'],
				'',
				$action['tagOptions']
			).' '.@$action['tagOptions']['text'],
			$action['action'].'/'.$model->id,
			$action['options']
		)."&nbsp;";
		break;
	}
	
}
echo Html::tag('small', $modelActions, ['class' => 'center-block text-center']);
?>