<?php

namespace dwy\FacebookConversion\services;

use ArrayAccess;
use Craft;
use craft\base\Component;
use craft\db\ActiveQuery;
use craft\db\Paginator;
use craft\helpers\Json;
use DateTimeImmutable;
use dwy\FacebookConversion\models\Event;
use dwy\FacebookConversion\Plugin;
use FacebookAds\Object\ServerSide\EventRequest;
use FacebookAds\Object\ServerSide\EventResponse;

class Events extends Component
{
    public const STATUS_SUCCESS = 'success';
    public const STATUS_ERROR = 'error';

    private const CLEANUP_BATCH_SIZE = 500;

    /**
     * @return Event[] saved log rows (one per event in the request)
     */
    public function logEvent(EventRequest $request, ?EventResponse $response = null, ?string $error = null): array
    {
        $settings = Plugin::getInstance()->getSettings();
        if ($settings->getEventLogRetentionDays() === 0) {
            return [];
        }

        $events = $request->getEvents();
        if ($events === null || !is_array($events) || $events === []) {
            Craft::warning('Facebook Conversion: EventRequest had no events to log.', __METHOD__);
            return [];
        }

        $status = self::computeStatus($response, $error);
        $responseJson = $response ? Json::encode(self::eventToArray($response)) : null;
        $now = new \DateTime();
        $rows = [];

        foreach ($events as $facebookEvent) {
            $event = new Event();
            $event->event_id = $this->_getEventId($facebookEvent);
            $event->event_name = $this->_getEventName($facebookEvent);
            $event->event_data = Json::encode(self::eventToArray($facebookEvent));
            $event->status = $status;
            $event->response = $responseJson;
            $event->error = $error;
            $event->created_at = $now;

            if ($event->save()) {
                $rows[] = $event;
            } else {
                Craft::error('Facebook Conversion: Failed to save event log: ' . Json::encode($event->getErrors()), __METHOD__);
            }
        }

        return $rows;
    }

    public function getEventsQuery(): ActiveQuery
    {
        return Event::find()->orderBy(['created_at' => SORT_DESC]);
    }

    public function getEvents(int $limit = 50, int $offset = 0): array
    {
        return $this
            ->getEventsQuery()
            ->limit($limit)
            ->offset($offset)
            ->all();
    }

    public function getEventsPaginatedQuery(int $pageNum, int $pageSize = 50): Paginator
    {
        return new Paginator($this->getEventsQuery(), [
            'currentPage' => $pageNum,
            'pageSize' => $pageSize,
        ]);
    }

    public function cleanup(): void
    {
        $days = Plugin::getInstance()->getSettings()->getEventLogRetentionDays();
        if ($days === 0) {
            return;
        }

        $cutoff = self::computeCutoff($days, new DateTimeImmutable());

        while (true) {
            $ids = Event::find()
                ->select(['id'])
                ->where(['<', 'created_at', $cutoff])
                ->orderBy(['id' => SORT_ASC])
                ->limit(self::CLEANUP_BATCH_SIZE)
                ->column();

            if ($ids === []) {
                break;
            }

            Event::deleteAll(['id' => $ids]);
        }
    }

    public function getTotalEvents(): int
    {
        return Event::find()->count();
    }

    /**
     * Returns the cutoff datetime string (DB format) for a retention window.
     *
     * Pure function — exposed as a static seam so tests can pin "now".
     */
    public static function computeCutoff(int $days, DateTimeImmutable $now): string
    {
        return $now->modify("-{$days} days")->format('Y-m-d H:i:s');
    }

    /**
     * Decides log row status from a Facebook API response and/or an error message.
     *
     * Pure function — exposed as a static seam for unit tests.
     */
    public static function computeStatus(?EventResponse $response = null, ?string $error = null): string
    {
        if ($error) {
            return self::STATUS_ERROR;
        }

        if ($response === null) {
            return self::STATUS_ERROR;
        }

        $eventsReceived = $response['events_received'] ?? null;
        if ($eventsReceived === null || (int) $eventsReceived === 0) {
            return self::STATUS_ERROR;
        }

        $messages = $response['messages'] ?? [];
        if (!is_array($messages)) {
            return self::STATUS_ERROR;
        }

        if (count($messages) > 0) {
            return self::STATUS_ERROR;
        }

        return self::STATUS_SUCCESS;
    }

    /**
     * Recursively converts FB SDK objects (or arrays of them) to plain arrays.
     *
     * Pure function — exposed as a static seam for unit tests.
     *
     * @return array<string, mixed>|null
     */
    public static function eventToArray(mixed $object): ?array
    {
        if ($object === null) {
            return null;
        }

        if (is_array($object)) {
            $out = [];
            foreach ($object as $k => $v) {
                $out[(string) $k] = is_object($v) || is_array($v) ? self::eventToArray($v) : $v;
            }
            return $out;
        }

        if (is_object($object) && method_exists($object, 'getters')) {
            $array = [];
            foreach ($object->getters() as $attribute => $getter) {
                if (!method_exists($object, $getter)) {
                    continue;
                }

                $value = $object->$getter();

                if (is_object($value) || is_array($value)) {
                    $value = self::eventToArray($value);
                }

                $array[$attribute] = $value;
            }
            return $array;
        }

        if ($object instanceof ArrayAccess) {
            // Best-effort: convert ArrayAccess to array via JSON encode/decode.
            return Json::decode(Json::encode($object));
        }

        return null;
    }

    private function _getEventId(mixed $facebookEvent): ?string
    {
        if (is_array($facebookEvent)) {
            return isset($facebookEvent['event_id']) ? (string) $facebookEvent['event_id'] : null;
        }

        if (is_object($facebookEvent) && method_exists($facebookEvent, 'getEventId')) {
            $id = $facebookEvent->getEventId();
            return $id !== null ? (string) $id : null;
        }

        if ($facebookEvent instanceof ArrayAccess && isset($facebookEvent['event_id'])) {
            return (string) $facebookEvent['event_id'];
        }

        return null;
    }

    private function _getEventName(mixed $facebookEvent): ?string
    {
        if (is_array($facebookEvent)) {
            return isset($facebookEvent['event_name']) ? (string) $facebookEvent['event_name'] : null;
        }

        if (is_object($facebookEvent) && method_exists($facebookEvent, 'getEventName')) {
            $name = $facebookEvent->getEventName();
            return $name !== null ? (string) $name : null;
        }

        if ($facebookEvent instanceof ArrayAccess && isset($facebookEvent['event_name'])) {
            return (string) $facebookEvent['event_name'];
        }

        return null;
    }
}
