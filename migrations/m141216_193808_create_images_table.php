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
            'file_name' => Schema::TYPE_STRING . '(555) NULL',
            'remote_type' => Schema::TYPE_STRING . '(555) NULL',
            'remote_class' => Schema::TYPE_STRING . '(555) NULL',
            'remote_id' => Schema::TYPE_INTEGER . ' NULL',
            'author_id' => Schema::TYPE_INTEGER . '(11) NULL',
            'editor_id' => Schema::TYPE_INTEGER . '(11) NULL',
            'owner_id' => Schema::TYPE_INTEGER . ' NULL',
            'type' => Schema::TYPE_STRING . '(45) NULL',
            'src' => Schema::TYPE_STRING . '(45) NULL',
            'html_icon' => Schema::TYPE_STRING . ' NULL',
            'width' => Schema::TYPE_INTEGER  . ' NULL',
            'height' => Schema::TYPE_INTEGER  . ' NULL',
            'created_at' => Schema::TYPE_TIMESTAMP  . ' DEFAULT NOW()',
            'updated_at' => Schema::TYPE_TIMESTAMP  . ' NULL',
            'signature' => Schema::TYPE_STRING  . ' ',
            'is_default' => Schema::TYPE_BOOLEAN  . ' DEFAULT false',
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
