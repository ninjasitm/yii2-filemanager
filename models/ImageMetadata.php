<?php

namespace nitm\filemanager\models;

use Yii;

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
class ImageMetadata extends FileMetadata
{

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
		return 'images_metadata';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['image_id', 'key', 'value'], 'required'],
            [['image_id', 'width', 'height', 'size'], 'integer'],
            [['created', 'updated'], 'safe'],
            [['image_id', 'key'], 'unique', 'targetAttribute' => ['image_id', 'key'], 'message' => 'The combination of Image ID and Key has already been taken.']
        ];
    }

	public function behaviors()
	{
		return parent::behaviors();
	}

	public function scenarios()
	{
		return [
			'create' => ['key', 'value', 'image_id', 'width', 'height', 'size'],
			'update' => ['key', 'value', 'width', 'height', 'size'],
		];
	}

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'image_id' => Yii::t('app', 'Image ID'),
            'key' => Yii::t('app', 'Key'),
            'value' => Yii::t('app', 'Value'),
            'created' => Yii::t('app', 'Created'),
            'updated' => Yii::t('app', 'Updated'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getImage()
    {
        return $this->hasOne(Images::className(), ['id' => 'image_id']);
    }

    /**
	 * The link that signifies the metadata connection
     * @return array
     */
    public function metadataLink()
    {
        return ['image_id' => 'id'];
    }
}
