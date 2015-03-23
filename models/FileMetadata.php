<?php

namespace nitm\filemanager\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "images_metadata".
 *
 * @property integer $image_id
 * @property string $key
 * @property string $value
 * @property string $created
 * @property string $updated
 *
 * @property Images $image
 */
class FileMetadata extends \yii\db\ActiveRecord
{
	
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
		return 'files_metadata';
    }
	
	public function behaviors()
	{
		$behaviors = [
			'timestamp' => [
				'class' => \yii\behaviors\TimestampBehavior::className(),
				'attributes' => [
					ActiveRecord::EVENT_BEFORE_UPDATE => 'updated_at',
				]
			],
			"blamable" => [
				'class' => \yii\behaviors\BlameableBehavior::className(),
				'attributes' => [
					ActiveRecord::EVENT_BEFORE_INSERT => 'author_id',
					ActiveRecord::EVENT_BEFORE_UPDATE => 'editor_id',
				],
			]
		];
		return array_merge(parent::behaviors(), $behaviors);
	}

    /**
	 * The link that signifies the metadata connection
     * @return array
     */
    public function metadataLink()
    {
        return ['id' => 'id'];
    }
}
