<?php

use yii\db\Migration;

/**
 * Class m210213_064358_add_typed_value_indexes
 */
class m210213_064358_add_typed_value_indexes extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createIndex('bool_values_typed_idx', 'form_data_values_typed', ['field','value_bool']);
        $this->createIndex('str_values_typed_idx', 'form_data_values_typed', ['field','value_str']);
        $this->createIndex('int_values_typed_idx', 'form_data_values_typed', ['field','value_int']);
        $this->createIndex('date_values_typed_idx', 'form_data_values_typed', ['field','value_date']);
        $this->createIndex('user_values_typed_idx', 'form_data_values_typed', ['field','value_userfk']);
        $this->createIndex('json_values_typed_idx', 'form_data_values_typed', ['field','value_json'], 'gin');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropIndex('json_values_typed_idx', 'form_data_values_typed');
        $this->dropIndex('user_values_typed_idx', 'form_data_values_typed');
        $this->dropIndex('bool_values_typed_idx', 'form_data_values_typed');
        $this->dropIndex('str_values_typed_idx', 'form_data_values_typed');
        $this->dropIndex('int_values_typed_idx', 'form_data_values_typed');
        $this->dropIndex('date_values_typed_idx', 'form_data_values_typed');
    }
}
