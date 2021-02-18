<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%form_data_json}}`.
 */
class m210212_220240_create_form_data_json_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->db->createCommand('CREATE EXTENSION IF NOT EXISTS btree_gin;')->execute();
        $this->createTable('{{%form_data_json}}', [
            'id' => $this->primaryKey(),
            'form_id' => $this->integer()->notNull(),
            'user_id' => $this->integer()->notNull(),
            'title' => $this->string(),
            'status' =>$this->string(),
            'values'=>$this->json(),
            'values_ind'=>$this->json(),
        ]);
        $this->addForeignKey('fk_form_data_json_user', 'form_data_json', 'user_id', 'users', 'id');
        $this->addForeignKey('fk_form_data_json_form', 'form_data_json', 'form_id', 'forms', 'id');
        $this->createIndex('form_data_json_form_id_idx', 'form_data_json', ['form_id']);
        $this->createIndex('form_data_json_values_idx', 'form_data_json', ['values_ind']);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropIndex('form_data_json_form_id_idx', 'form_data_json');
        $this->dropIndex('form_data_json_values_idx', 'form_data_json');
        $this->dropTable('{{%form_data_json}}');
    }
}
