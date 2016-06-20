<?php

use yii\db\Schema;
use yii\db\Migration;

class m141216_193808_create_images_table extends Migration
{
    public function up()
    {
		$tableSchema = \Yii::$app->db->getTableSchema('images');
		if($tableSchema)
			return true;

        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%images}}', [
            'id' => Schema::TYPE_PK,
            'author_id' => Schema::TYPE_INTEGER,
            'editor_id' => Schema::TYPE_INTEGER . ' NULL',
            'owner_id' => Schema::TYPE_INTEGER . ' NULL',
            'url' => Schema::TYPE_STRING . '(555) NULL',
            'thumbnail_url' => Schema::TYPE_STRING . '(555) NULL',
            'file_name' => Schema::TYPE_STRING . '(255) NULL',
            'remote_id' => Schema::TYPE_INTEGER . ' NOT NULL',
            'remote_type' => Schema::TYPE_STRING . '(45) NULL',
            'remote_class' => Schema::TYPE_STRING . '(45) NULL',
            'type' => Schema::TYPE_STRING . '(64) NULL',
            'slug' => Schema::TYPE_STRING . '(64)',
            'title' => Schema::TYPE_STRING . '(64) NULL',
            'size' => Schema::TYPE_INTEGER . ' NULL',
            'width' => Schema::TYPE_INTEGER  . ' NULL',
            'height' => Schema::TYPE_INTEGER  . ' NULL',
            'hash' => Schema::TYPE_STRING.' ',
            'created_at' => Schema::TYPE_TIMESTAMP  . ' DEFAULT NOW()',
            'updated_at' => Schema::TYPE_TIMESTAMP  . ' NULL',
            'date' => Schema::TYPE_DATETIME  . ' NULL',
            'date_gmt' => Schema::TYPE_DATETIME  . ' NULL',
            'update' => Schema::TYPE_DATETIME  . ' NULL',
            'update_gmt' => Schema::TYPE_DATETIME  . ' NULL',
            'deleted_by' => Schema::TYPE_INTEGER  . ' NULL',
            'deleted_at' => Schema::TYPE_DATETIME  . ' NULL',
            'deleted' => Schema::TYPE_BOOLEAN  . ' NULL',
        ], $tableOptions);

		$this->createIndex('images_unique', '{{%files}}', [
			'remote_id', 'remote_type', 'author_id', 'signature'
		], true);

        $this->createTable('{{%images_metadata}}', [
            'id' => Schema::TYPE_PK,
            'image_id' => Schema::TYPE_INTEGER . "(11) NOT NULL",
            'key' => Schema::TYPE_STRING . '(45) NULL',
            'value' => Schema::TYPE_TEXT . ' NULL',
            'author_id' => Schema::TYPE_INTEGER . '(11) NULL',
            'editor_id' => Schema::TYPE_INTEGER . '(11) NULL',
            'created_at' => Schema::TYPE_TIMESTAMP  . ' DEFAULT NOW()',
            'updated_at' => Schema::TYPE_TIMESTAMP  . ' NULL',
            'width' => Schema::TYPE_INTEGER . '(11) NULL',
            'height' => Schema::TYPE_INTEGER . '(11) NULL',
            'size' => Schema::TYPE_INTEGER . '(11) NULL',
        ], $tableOptions);

        $this->addForeignKey('FK_images_metadata','{{%images_metadata}}','image_id','{{%images}}','id');
        $this->addForeignKey('FK_images_author','{{%images}}','author_id','{{%user}}','id');

    }

    public function down()
    {
        $this->dropForeignKey('FK_images_terms','{{%images_metadata}}');
        $this->dropForeignKey('FK_images_users','{{%images}}');
        $this->dropTable('{{%images}}');
        $this->dropTable('{{%images_metadata}}');
    }
}
