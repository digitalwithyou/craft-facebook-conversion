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
            ['pixelId', 'string'],
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
}
