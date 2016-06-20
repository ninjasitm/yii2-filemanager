<?php
/** @var \dosamigos\fileupload\FileUploadUI $this */
use yii\helpers\Html;
use kartik\tabs\TabsX;
use dosamigos\fileupload\FileUploadUI;

$context = $this->context;
?>
<!-- The file upload form used as target for the file upload widget -->
<?php
    if($widget->enableTabs) {
        echo TabsX::widget([
            'items' => [
                [
                    'label' => 'Upload files',
                    'content' => FileUploadUI::widget($widgetOptions)
                ], [
                    'label' => 'Upload urls',
                    'visible' => $widget->enableUrlUpload,
                    'content' => FileUploadUI::widget(array_replace_recursive($widgetOptions, [
                        'options' => [
                            'id' => 'url-upload-'.$model->getId('-', ['remote_type', 'remote_id'])
                        ],
                        'formView' => '@nitm/filemanager/views/upload/urls'
                    ]))
                ]
            ]
        ]);
    } else {
        echo FileUploadUI::widget($widgetOptions);
        if($widget->enableUrlUpload) {
            echo FileUploadUI::widget(array_replace_recursive($widgetOptions, [
                'options' => [
                    'id' => 'url-upload-'.$model->getId('-', ['remote_type', 'remote_id'])
                ],
                'formView' => '@nitm/filemanager/views/upload/urls'
            ]));
        }
    }
?>
