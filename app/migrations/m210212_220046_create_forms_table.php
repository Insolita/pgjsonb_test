<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%forms}}`.
 */
class m210212_220046_create_forms_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%forms}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string(),
            'fields'=>$this->json()
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%forms}}');
    }
}
