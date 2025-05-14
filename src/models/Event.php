<?php

namespace dwy\FacebookConversion\models;

use craft\base\Model;
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
            [['event_name', 'event_data'], 'required'],
            [['event_data'], 'string'],
            [['event_name'], 'string', 'max' => 255],
            [['status'], 'string', 'max' => 50],
            [['response'], 'string'],
            [['created_at'], 'safe'],
        ];
    }
}
