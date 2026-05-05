<?php

namespace dwy\FacebookConversion\models;

use Craft;
use craft\base\Model;
use craft\behaviors\EnvAttributeParserBehavior;

class Settings extends Model
{
    /**
     * @var string
     */
    public $pixelId;

    /**
     * @var string
     */
    public $accessToken;

    /**
     * @var string
     */
    public $testEventCode;

    /**
     * Days to keep rows in the event log. Use 0 to disable storing new log rows.
     *
     * @var int
     */
    public $eventLogRetentionDays = 30;

    public function behaviors(): array
    {
        return [
            'parser' => [
                'class' => EnvAttributeParserBehavior::class,
                'attributes' => ['pixelId', 'accessToken'],
            ],
        ];
    }

    public function rules(): array
    {
        return [
            [['pixelId', 'accessToken'], 'required'],
            [['pixelId', 'accessToken', 'testEventCode'], 'string'],
            [['eventLogRetentionDays'], 'integer', 'min' => 0, 'max' => 3650],
            [['eventLogRetentionDays'], 'default', 'value' => 30],
        ];
    }

    public function getPixelId(): ?string
    {
        return Craft::parseEnv($this->pixelId);
    }

    public function getAccessToken(): ?string
    {
        return Craft::parseEnv($this->accessToken);
    }

    public function getTestEventCode(): ?string
    {
        return Craft::parseEnv($this->testEventCode);
    }

    public function getEventLogRetentionDays(): int
    {
        return (int) $this->eventLogRetentionDays;
    }
}
