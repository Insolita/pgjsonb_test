<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%form_data_values}}`.
 */
class m210212_220325_create_form_data_values_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%form_data_values}}', [
            'id' => $this->primaryKey(),
            'data_id' => $this->integer()->notNull(),
            'field' => $this->string(),
            'value' => $this->string()
        ]);
        $this->addForeignKey('fk_value_data', 'form_data_values', 'data_id', 'form_data_raw', 'id');
        $this->createIndex('field_value_idx', 'form_data_values', ['data_id', 'field', 'value']);

        $this->createTable('{{%form_data_values_typed}}', [
            'id' => $this->primaryKey(),
            'data_id' => $this->integer()->notNull(),
            'field' => $this->string(),
            'value_bool' => $this->boolean(),
            'value_int' => $this->integer()->null(),
            'value_str' => $this->string()->null(),
            'value_date' => $this->date()->null(),
            'value_userfk' => $this->integer()->null(),
            'value_json' => $this->json(),
        ]);
        $this->addForeignKey('fk_value_typed_data', 'form_data_values_typed', 'data_id', 'form_data_raw', 'id');
        $this->addForeignKey('fk_value_typed_userfk', 'form_data_values_typed', 'value_userfk', 'users', 'id');
        $this->createIndex('field_data_idx', 'form_data_values_typed', ['data_id', 'field']);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropIndex('field_value_idx', 'form_data_values');
        $this->dropIndex('field_data_idx', 'form_data_values_typed');
        $this->dropTable('{{%form_data_values_typed}}');
        $this->dropTable('{{%form_data_values}}');
    }
}
