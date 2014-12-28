<?php

namespace nitm\filemanager\models;

use Yii;

/**
 * This is the model class for table "images".
 *
 * @property integer $id
 * @property integer $category_id
 * @property integer $content_id
 * @property string $url
 * @property string $slug
 * @property string $created
 * @property string $updated
 *
 * @property Content $content
 * @property Categories $category
 * @property ImagesMetadata $metadata
 */
class Image extends \nitm\filemanager\models\File
{
	use \nitm\filemanager\traits\ImageTraits;
		
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'images';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['remote_id', 'width', 'height'], 'integer'],
            [['url', 'slug', 'hash'], 'required'],
            [['url'], 'string'],
            [['created', 'updated'], 'safe'],
            [['slug', 'remote_type'], 'string', 'max' => 150],
            [['remote_id', 'hash'], 'unique', 'targetAttribute' => ['remote_id', 'hash'], 'message' => 'This image already exists'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'category_id' => Yii::t('app', 'Category ID'),
            'content_id' => Yii::t('app', 'Content ID'),
            'url' => Yii::t('app', 'Src'),
            'slug' => Yii::t('app', 'Slug'),
            'created' => Yii::t('app', 'Created'),
            'updated' => Yii::t('app', 'Updated'),
        ];
	}
}
