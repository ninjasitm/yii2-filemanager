<?php

namespace nitm\filemanager\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\behaviors\AttributeBehavior;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "files".
 *
 * @property integer $id
 * @property integer $author_id
 * @property string $url
 * @property string $thumbnail_url
 * @property string $file_name
 * @property integer $remote_id
 * @property string $remote_type
 * @property string $type
 * @property string $title
 * @property integer $size
 * @property integer $width
 * @property integer $height
 * @property string $date
 * @property string $date_gmt
 * @property string $update
 * @property string $update_gmt
 *
 * @property FileTerms[] $fileTerms
 * @property User $user
 */
 
class File extends BaseFile
{	
    public function behaviors()
    {
		$behaviors = [
		];
        return array_merge(parent::behaviors(), $behaviors);
    }
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'files';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['author_id', 'size'], 'integer'],
            [['date', 'date_gmt', 'update', 'update_gmt'], 'safe'],
            [['url', 'thumbnail_url', 'file_name', 'title'], 'string', 'max' => 555],
            [['type'], 'string', 'max' => 45],
            [['remote_type', 'remote_id', 'hash'], 'unique', 'targetAttribute' => ['remote_id', 'hash'], 'message' => 'This file already exists', 'on' => ['create']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'author_id' => 'User ID',
            'url' => 'Location',
            'thumbnail_url' => 'Thumbnail Url',
            'file_name' => 'File Name',
            'type' => 'Type',
            'title' => 'Title',
            'size' => 'Size',
            'width' => 'Width',
            'height' => 'Height',
            'date' => 'Date',
            'date_gmt' => 'Date Gmt',
            'update' => 'Update',
            'update_gmt' => 'Update Gmt',
        ];
    }
}
