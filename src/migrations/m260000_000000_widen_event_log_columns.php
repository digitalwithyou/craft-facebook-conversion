<?php

namespace dwy\FacebookConversion\migrations;

use craft\db\Migration;
use dwy\FacebookConversion\models\Event;

class m260000_000000_widen_event_log_columns extends Migration
{
    public function safeUp(): void
    {
        $table = Event::tableName();

        if (!$this->db->tableExists($table)) {
            return;
        }

        // Postgres `text` is already unbounded; only MySQL/MariaDB benefit from MEDIUMTEXT.
        if ($this->db->getIsMysql()) {
            $this->alterColumn($table, 'event_data', $this->mediumText());
            $this->alterColumn($table, 'response', $this->mediumText());
            $this->alterColumn($table, 'error', $this->mediumText());
        }

        $this->createIndex(null, $table, ['status', 'created_at'], false);
        $this->createIndex(null, $table, 'event_name', false);
    }

    public function safeDown(): void
    {
        // Intentionally no-op: shrinking back to TEXT could truncate data.
    }
}
