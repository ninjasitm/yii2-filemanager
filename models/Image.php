<?php

namespace nitm\filemanager\models;

use Yii;

/**
 * This is the model class for table "images".
 *
 * @property integer $id
 * @property string $remote_type
 * @property integer $remote_id
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
            'remote_id' => Yii::t('app', 'Content ID'),
            'url' => Yii::t('app', 'Src'),
            'slug' => Yii::t('app', 'Slug'),
            'created' => Yii::t('app', 'Created'),
            'updated' => Yii::t('app', 'Updated'),
        ];
	}
	
	/**
	 * Get all the images for this entity
	 * @param boolean $thumbnails Get thumbnails as well?
	 * @param boolean $default Get the default image as well?
	 */
	public static function getImagesFor($model, $thumbnails=false, $default=false, $limit=10)
	{
        $ret_val = $model->hasMany(Image::className(), ['remote_id' => 'id']);
		$with = [];
		switch($default === true)
		{
			case false:
			$ret_val->andWhere('is_default=true');
			break;
		}
		switch($thumbnails)
		{
			case true:
			$with[] = 'metadata';
			break;
		}
		$ret_val->with($with);
		$ret_val->limit($limit);
		$ret_val->andWhere(['remote_type' => $model->isWhat()]);
		return $ret_val;
	}
	
	/**
	 * Get the main icon for this entity
	 */
	public static function getIconFor($model)
	{
        return $model->hasOne(Image::className(), ['remote_id' => 'id'])->where(['remote_type' => $model->isWhat()])
		->andWhere('is_default=true');
	}
}
