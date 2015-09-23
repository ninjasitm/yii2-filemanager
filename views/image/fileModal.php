<?php
use dosamigos\fileupload\FileUploadUI;


// without UI
$this->title = 'Image Upload';
?>


<?= FileUploadUI::widget([
    'model' => $model,
    'attribute' => 'file_name',
    'url' => ['images/upload'], // your url, this is just for demo purposes,
    'options' => ['accept' => 'image/*'],
    'clientOptions' => [
        'maxFileSize' => 2000000
    ]
]);?>
