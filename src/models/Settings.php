<?php

namespace dwy\FacebookConversion\models;

use Craft;
use craft\base\Model;
use craft\behaviors\EnvAttributeParserBehavior;

class Settings extends Model
{
    /**
     * @var integer
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
            ['pixelId', 'integer'],
        ];
    }

    public function getPixelId(): ?int
    {
        return Craft::parseEnv($this->pixelId);
    }

    public function getAccessToken(): ?string
    {
        return Craft::parseEnv($this->accessToken);
    }
}
