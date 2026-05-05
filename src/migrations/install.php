<?php

namespace dwy\FacebookConversion\migrations;

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
                'event_data' => $this->mediumText(),
                'status' => $this->string(50)->notNull(),
                'response' => $this->mediumText(),
                'error' => $this->mediumText(),
                'created_at' => $this->dateTime()->notNull(),
            ]);
            $this->createIndex(null, Event::tableName(), 'created_at', false);
            $this->createIndex(null, Event::tableName(), ['status', 'created_at'], false);
            $this->createIndex(null, Event::tableName(), 'event_name', false);
        }
    }

    public function safeDown()
    {
        if ($this->db->tableExists(Event::tableName())) {
            $this->dropTable(Event::tableName());
        }
    }
}
