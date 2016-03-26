<?php
/** @var \dosamigos\fileupload\FileUploadUI $this */
use yii\helpers\Html;
use kartik\tabs\TabsX;
use dosamigos\fileupload\FileUploadUI;

$context = $this->context;
?>
    <!-- The file upload form used as target for the file upload widget -->
<?= TabsX::widget([
    'items' => [
        [
            'label' => 'Upload files',
            'content' => FileUploadUI::widget($widgetOptions)
        ], [
            'label' => 'Upload urls',
            'content' => FileUploadUI::widget(array_replace_recursive($widgetOptions, [
                'options' => [
                    'id' => 'url-upload-'.$model->getId('-', ['remote_type', 'remote_id'])
                ],
                'formView' => '@nitm/filemanager/views/upload/urls'
            ]))
        ]
    ]
]); ?>
