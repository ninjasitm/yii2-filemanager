<?php
/** @var \dosamigos\fileupload\FileUploadUI $this */
use yii\helpers\Html;
use kartik\tabs\TabsX;
use dosamigos\fileupload\FileUpload;

$context = $this->context;
?>
    <!-- The file upload form used as target for the file upload widget -->
<?= FileUpload::widget($widgetOptions) ?>
