<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%form_data_raw}}`.
 */
class m210212_220302_create_form_data_raw_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%form_data_raw}}', [
            'id' => $this->primaryKey(),
            'form_id' => $this->bigInteger()->notNull(),
            'user_id' => $this->integer()->notNull(),
            'title' => $this->string(),
            'status' =>$this->string(),
        ]);
        $this->addForeignKey('fk_form_data_raw_user', 'form_data_raw', 'user_id', 'users', 'id');
        $this->addForeignKey('fk_form_data_raw_form', 'form_data_raw', 'form_id', 'forms', 'id');
        $this->createIndex('form_data_form_id_idx', 'form_data_raw', ['form_id']);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropIndex('form_data_form_id_idx', 'form_data_raw');
        $this->dropTable('{{%form_data_raw}}');
    }
}
