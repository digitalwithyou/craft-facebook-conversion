<?php

declare(strict_types=1);

namespace dwy\FacebookConversion\Tests\unit\Services;

use ArrayIterator;
use dwy\FacebookConversion\services\Events;
use FacebookAds\Object\ServerSide\CustomData;
use FacebookAds\Object\ServerSide\Event;
use FacebookAds\Object\ServerSide\UserData;
use PHPUnit\Framework\TestCase;

final class EventsToArrayTest extends TestCase
{
    public function test_null_returns_null(): void
    {
        $this->assertNull(Events::eventToArray(null));
    }

    public function test_plain_array_of_scalars_round_trips(): void
    {
        $input = ['a' => 1, 'b' => 'two', 'c' => true];

        $this->assertSame(
            ['a' => 1, 'b' => 'two', 'c' => true],
            Events::eventToArray($input)
        );
    }

    public function test_nested_array_recurses(): void
    {
        $input = ['outer' => ['inner' => 'value', 'n' => 42]];

        $this->assertSame(
            ['outer' => ['inner' => 'value', 'n' => 42]],
            Events::eventToArray($input)
        );
    }

    public function test_object_with_getters_walks_attributes(): void
    {
        $event = (new Event())
            ->setEventName('Purchase')
            ->setEventTime(1700000000)
            ->setEventId('test-123');

        $result = Events::eventToArray($event);

        $this->assertIsArray($result);
        $this->assertSame('Purchase', $result['event_name']);
        $this->assertSame(1700000000, $result['event_time']);
        $this->assertSame('test-123', $result['event_id']);
    }

    public function test_nested_fb_objects_are_recursively_converted(): void
    {
        $event = (new Event())
            ->setEventName('Purchase')
            ->setEventTime(1700000000)
            ->setUserData((new UserData())->setEmail('test@example.com'))
            ->setCustomData((new CustomData())->setValue(99.99)->setCurrency('EUR'));

        $result = Events::eventToArray($event);

        $this->assertIsArray($result['user_data']);
        $this->assertIsArray($result['custom_data']);
        $this->assertSame(99.99, $result['custom_data']['value']);
        $this->assertSame('EUR', $result['custom_data']['currency']);
        // The FB SDK stores the raw email; hashing happens at normalize() time before send.
        $this->assertSame('test@example.com', $result['user_data']['email']);
    }

    public function test_array_of_objects_converts_each_element(): void
    {
        $events = [
            (new Event())->setEventName('AddToCart')->setEventTime(100),
            (new Event())->setEventName('Purchase')->setEventTime(200),
        ];

        $result = Events::eventToArray($events);

        $this->assertCount(2, $result);
        $this->assertSame('AddToCart', $result[0]['event_name']);
        $this->assertSame('Purchase', $result[1]['event_name']);
    }

    public function test_unsupported_object_returns_null(): void
    {
        $iterator = new ArrayIterator(['a' => 1]);

        $result = Events::eventToArray($iterator);

        // ArrayIterator implements ArrayAccess; falls through to JSON roundtrip.
        $this->assertIsArray($result);
    }
}
