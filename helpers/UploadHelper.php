<?php

namespace nitm\filemanager\helpers;

use Yii;
use yii\helpers\Html;
use yii\helpers\Inflector;
use yii\helpers\FileHelper;
use yii\web\UploadedFile;
use yii\imagine\Image as BaseImage;
use kartik\icons\Icon;
use nitm\filemanager\helpers\Storage;
use nitm\filemanager\models\Image;
use nitm\filemanager\models\ImageMetadata;
use Imagick;

/**
 * File upload helper. Porived some useful functions that help ease the upload process
 *
 */
class UploadHelper
{

	/**
	 * Reads data from an HTTP PUT request. PUT only handles a single file at a time
	 * @return UploadedFile|null The uploaded file
	 */
	public function getDataFromStdIn()
	{
		if(($data = fopen('php://input', 'r')) !== null) {
			$tmpFile = '/tmp/'.\yii::$app->getSecurity()->generateRandomString(15);
			fclose(fopen($tmpFile, 'x')); //Create the temportary file
			$size = 0;
			if($fp = fopen($tmpFile, 'w')) {
				while ($chunk = fread($data, 8192)) {
					$length = strlen($chunk);
					echo "Read $length bytes\n";
					if(($written = fwrite($fp, $chunk)) != $length) {
						\Yii::warning("Error writintg to file from std in.");
						fclose($fp);
						unlink($tmpFile);
						return null;
					}
					$size += $written;
				}
				fclose($fp);
				$file = new UploadedFile([
					'tempName' => $tmpFile,
					'type' => FileHelper::getMimeType($tmpFile),
					'size' => $size
				]);
				$file->name = $file->getBaseName();
				return $file;
			} else {
				return null;
			}
		}
		return null;
	}
}
