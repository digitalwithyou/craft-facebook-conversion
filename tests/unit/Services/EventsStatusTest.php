<?php

declare(strict_types=1);

namespace dwy\FacebookConversion\Tests\unit\Services;

use dwy\FacebookConversion\services\Events;
use FacebookAds\Object\ServerSide\EventResponse;
use PHPUnit\Framework\TestCase;

final class EventsStatusTest extends TestCase
{
    public function test_error_message_returns_error_status(): void
    {
        $this->assertSame(
            Events::STATUS_ERROR,
            Events::computeStatus(null, 'boom')
        );
    }

    public function test_error_takes_precedence_over_response(): void
    {
        $response = new EventResponse(['events_received' => 1, 'messages' => []]);

        $this->assertSame(
            Events::STATUS_ERROR,
            Events::computeStatus($response, 'queue exception')
        );
    }

    public function test_null_response_and_null_error_is_error(): void
    {
        $this->assertSame(
            Events::STATUS_ERROR,
            Events::computeStatus(null, null)
        );
    }

    public function test_zero_events_received_is_error(): void
    {
        $response = new EventResponse(['events_received' => 0, 'messages' => []]);

        $this->assertSame(
            Events::STATUS_ERROR,
            Events::computeStatus($response, null)
        );
    }

    public function test_missing_events_received_is_error(): void
    {
        $response = new EventResponse(['messages' => []]);

        $this->assertSame(
            Events::STATUS_ERROR,
            Events::computeStatus($response, null)
        );
    }

    public function test_clean_response_is_success(): void
    {
        $response = new EventResponse(['events_received' => 1, 'messages' => []]);

        $this->assertSame(
            Events::STATUS_SUCCESS,
            Events::computeStatus($response, null)
        );
    }

    public function test_response_with_warning_messages_is_error(): void
    {
        $response = new EventResponse([
            'events_received' => 1,
            'messages' => ['Some user parameters are not valid hashed values'],
        ]);

        $this->assertSame(
            Events::STATUS_ERROR,
            Events::computeStatus($response, null)
        );
    }
}
