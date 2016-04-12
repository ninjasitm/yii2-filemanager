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
use nitm\helpers\Network;
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
	public function getFromStdIn()
	{
		if(($data = fopen('php://input', 'r')) !== null) {
			return static::createTempFile($data);
		}
		return null;
	}

	protected static function createTempFile($data)
	{
		$tmpFile = '/tmp/php'.\yii::$app->getSecurity()->generateRandomString(6);
		fclose(fopen($tmpFile, 'x')); //Create the temportary file
		$size = $error = 0;
		if($fp = fopen($tmpFile, 'w')) {
			if(is_resource($data)) {
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
			} else if(is_string($data)) {
				fwrite($fp, $data);
				$size = strlen($data);
			} else {
				$error = true;
			}
			fclose($fp);
			if(!$error) {
				$file = new UploadedFile([
					'tempName' => $tmpFile,
					'size' => $size
				]);
				$file->name = $file->getBaseName();
				return $file;
			} else {
				unlink($tmpFile);
				return null;
			}
		}
		return null;
	}

	/**
	 * Gets the $uploadedFile object from a url
	 * @param string $url
	 * @param
	 * @return UploadedFile|null The uploaded file
	 */
	public function getFromUrl($url)
	{
		if(Network::isValidUrl($url)) {
			$file = static::createTempFile(\nitm\helpers\Network::getCurlData($url));
			$file->name = basename($url);
			$file->type = FileHelper::getMimeTypeByExtension($file->name);
			return [$file, getimagesize($url)];
		}
		return null;
	}
}
