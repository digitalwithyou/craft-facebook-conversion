<?php

namespace dwy\FacebookConversion\models;

use craft\db\ActiveRecord;

class Event extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%facebook_conversion_events}}';
    }

    public function rules(): array
    {
        return [
            [['event_name', 'event_data', 'status'], 'required'],
            [['event_data'], 'string'],
            [['event_name'], 'string', 'max' => 255],
            [['status'], 'string', 'max' => 50],
            [['response'], 'string'],
            [['error'], 'string'],
            [['created_at'], 'safe'],
        ];
    }
}
