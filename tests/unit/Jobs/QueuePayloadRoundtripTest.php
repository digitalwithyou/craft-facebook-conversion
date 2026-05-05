<?php

declare(strict_types=1);

namespace dwy\FacebookConversion\Tests\unit\Jobs;

use FacebookAds\Object\ServerSide\Content;
use FacebookAds\Object\ServerSide\CustomData;
use FacebookAds\Object\ServerSide\Event;
use FacebookAds\Object\ServerSide\EventRequest;
use FacebookAds\Object\ServerSide\UserData;
use PHPUnit\Framework\TestCase;

/**
 * Locks down the queue payload contract for SendFacebookConversionEvent:
 *
 * 1. A real EventRequest survives serialize() → unserialize() with the restricted
 *    allow-list and exposes the same event_id, event_name, custom data, etc.
 * 2. The allow-list rejects unrelated classes (returns __PHP_Incomplete_Class).
 *
 * Keep the allow-list in sync with src/jobs/SendFacebookConversionEvent.php.
 */
final class QueuePayloadRoundtripTest extends TestCase
{
    private const ALLOWED_CLASSES = [
        EventRequest::class,
        Event::class,
        UserData::class,
        CustomData::class,
        Content::class,
    ];

    public function test_event_request_roundtrip_preserves_event_metadata(): void
    {
        $event = (new Event())
            ->setEventName('Purchase')
            ->setEventTime(1700000000)
            ->setEventId('test-123')
            ->setActionSource('website')
            ->setEventSourceUrl('https://example.com/order/42')
            ->setUserData((new UserData())->setEmail('buyer@example.com'))
            ->setCustomData(
                (new CustomData())
                    ->setValue(99.99)
                    ->setCurrency('EUR')
            );

        $request = (new EventRequest('123456789'))->setEvents([$event]);

        $serialized = serialize($request);
        $restored = unserialize($serialized, ['allowed_classes' => self::ALLOWED_CLASSES]);

        $this->assertInstanceOf(EventRequest::class, $restored);
        $events = $restored->getEvents();
        $this->assertCount(1, $events);
        $this->assertSame('Purchase', $events[0]->getEventName());
        $this->assertSame('test-123', $events[0]->getEventId());
        $this->assertSame(1700000000, $events[0]->getEventTime());
        $this->assertSame('website', $events[0]->getActionSource());
        $this->assertSame('https://example.com/order/42', $events[0]->getEventSourceUrl());
        $this->assertSame(99.99, $events[0]->getCustomData()->getValue());
        $this->assertSame('EUR', $events[0]->getCustomData()->getCurrency());
    }

    public function test_batched_request_roundtrip_preserves_all_events(): void
    {
        $events = [
            (new Event())->setEventName('AddToCart')->setEventTime(100)->setActionSource('website'),
            (new Event())->setEventName('Purchase')->setEventTime(200)->setActionSource('website'),
            (new Event())->setEventName('Search')->setEventTime(300)->setActionSource('website'),
        ];
        $request = (new EventRequest('123456789'))->setEvents($events);

        $restored = unserialize(
            serialize($request),
            ['allowed_classes' => self::ALLOWED_CLASSES]
        );

        $this->assertInstanceOf(EventRequest::class, $restored);
        $restoredEvents = $restored->getEvents();
        $this->assertCount(3, $restoredEvents);
        $this->assertSame('AddToCart', $restoredEvents[0]->getEventName());
        $this->assertSame('Purchase', $restoredEvents[1]->getEventName());
        $this->assertSame('Search', $restoredEvents[2]->getEventName());
    }

    public function test_unrelated_class_is_rejected_by_allow_list(): void
    {
        $unrelated = serialize(new \DateTimeImmutable('2026-01-01'));

        $restored = unserialize($unrelated, ['allowed_classes' => self::ALLOWED_CLASSES]);

        $this->assertInstanceOf(\__PHP_Incomplete_Class::class, $restored);
    }
}
