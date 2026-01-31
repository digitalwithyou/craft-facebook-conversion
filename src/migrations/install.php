<?php

namespace dwy\FacebookConversion\migrations;

use Craft;
use craft\db\Migration;
use dwy\FacebookConversion\models\Event;

class Install extends Migration
{
    public function safeUp()
    {
        if (!$this->db->tableExists(Event::tableName())) {
            $this->createTable(Event::tableName(), [
                'id' => $this->primaryKey(),
                'event_id' => $this->string(),
                'event_name' => $this->string(),
                'event_data' => $this->text(),
                'status' => $this->string(50)->notNull(),
                'response' => $this->text(),
                'error' => $this->text(),
                'created_at' => $this->dateTime()->notNull(),
            ]);
        }
    }

    public function safeDown()
    {
        if ($this->db->tableExists(Event::tableName())) {
            $this->dropTable(Event::tableName());
        }
    }
}
