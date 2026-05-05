<?php

declare(strict_types=1);

namespace dwy\FacebookConversion\Tests\unit\Models;

use dwy\FacebookConversion\models\Settings;
use PHPUnit\Framework\TestCase;

final class SettingsRulesTest extends TestCase
{
    private function makeSettings(array $overrides = []): Settings
    {
        $settings = new TestableSettings();
        foreach ($overrides as $key => $value) {
            $settings->$key = $value;
        }

        return $settings;
    }

    public function test_default_retention_is_thirty_days(): void
    {
        $settings = $this->makeSettings();

        $this->assertSame(30, $settings->getEventLogRetentionDays());
    }

    public function test_zero_retention_is_valid(): void
    {
        $settings = $this->makeSettings([
            'pixelId' => '123456789',
            'accessToken' => 'abc',
            'eventLogRetentionDays' => 0,
        ]);

        $this->assertTrue($settings->validate());
        $this->assertSame(0, $settings->getEventLogRetentionDays());
    }

    public function test_max_retention_3650_is_valid(): void
    {
        $settings = $this->makeSettings([
            'pixelId' => '123456789',
            'accessToken' => 'abc',
            'eventLogRetentionDays' => 3650,
        ]);

        $this->assertTrue($settings->validate());
    }

    public function test_negative_retention_is_invalid(): void
    {
        $settings = $this->makeSettings([
            'pixelId' => '123456789',
            'accessToken' => 'abc',
            'eventLogRetentionDays' => -1,
        ]);

        $this->assertFalse($settings->validate());
        $this->assertArrayHasKey('eventLogRetentionDays', $settings->getErrors());
    }

    public function test_retention_above_max_is_invalid(): void
    {
        $settings = $this->makeSettings([
            'pixelId' => '123456789',
            'accessToken' => 'abc',
            'eventLogRetentionDays' => 3651,
        ]);

        $this->assertFalse($settings->validate());
        $this->assertArrayHasKey('eventLogRetentionDays', $settings->getErrors());
    }

    public function test_missing_pixel_id_fails_validation(): void
    {
        $settings = $this->makeSettings([
            'accessToken' => 'abc',
        ]);

        $this->assertFalse($settings->validate());
        $this->assertArrayHasKey('pixelId', $settings->getErrors());
    }

    public function test_missing_access_token_fails_validation(): void
    {
        $settings = $this->makeSettings([
            'pixelId' => '123456789',
        ]);

        $this->assertFalse($settings->validate());
        $this->assertArrayHasKey('accessToken', $settings->getErrors());
    }

    public function test_non_string_access_token_fails_validation(): void
    {
        $settings = $this->makeSettings([
            'pixelId' => '123456789',
            'accessToken' => ['array', 'is', 'not', 'a', 'string'],
        ]);

        $this->assertFalse($settings->validate());
        $this->assertArrayHasKey('accessToken', $settings->getErrors());
    }

    public function test_non_string_test_event_code_fails_validation(): void
    {
        $settings = $this->makeSettings([
            'pixelId' => '123456789',
            'accessToken' => 'abc',
            'testEventCode' => ['nope'],
        ]);

        $this->assertFalse($settings->validate());
        $this->assertArrayHasKey('testEventCode', $settings->getErrors());
    }
}

/**
 * Subclass that disables behaviors so validation does not require the Craft
 * application container (EnvAttributeParserBehavior calls App::parseEnv → Craft::getAlias).
 */
class TestableSettings extends Settings
{
    public function behaviors(): array
    {
        return [];
    }
}
