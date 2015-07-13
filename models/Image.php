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
	public static function getImagesFor($model, $thumbnails=false, $default=false, $queryOptions=null)
	{
		$queryOptions = is_null($queryOptions) ? ['where' => ['remote_type' => $model->isWhat()]] : $queryOptions;
        $ret_val = $model->hasMany(Image::className(), ['remote_id' => 'id']);
		$with = [];
		
		switch($default === true)
		{
			case true:
			$ret_val->andWhere(['is_default' => true]);
			break;
		}
		switch($thumbnails)
		{
			case true:
			array_push($with, 'icon');
			break;
		}
		
		$queryOptions = array_merge([
			'limit' => 10,
			'orderBy' => ['is_default' => SORT_DESC],
			'with' => $with
		], (array)$queryOptions);
			
		foreach($queryOptions as $option=>$params)
			if($ret_val->hasMethod($option))
				$ret_val->$option($params);
			
		return $ret_val;
	}
	
	/**
	 * Get the main icon for this entity
	 */
	public static function getIconFor($model, $queryOptions=null)
	{
		$queryOptions = is_null($queryOptions) ? ['where' => ['remote_type' => $model->isWhat()]] : $queryOptions;
        $query = $model->hasOne(Image::className(), ['remote_id' => 'id'])
			->andWhere('is_default=true')->with('metadata');
		foreach($queryOptions as $option=>$params)
			$query->$option($params);
		return $query;
	}
}
