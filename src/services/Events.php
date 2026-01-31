<?php

namespace dwy\FacebookConversion\services;

use ArrayAccess;
use Craft;
use craft\base\Component;
use craft\db\ActiveQuery;
use craft\db\Paginator;
use dwy\FacebookConversion\models\Event;
use FacebookAds\Object\ServerSide\Event as FacebookEvent;
use FacebookAds\Object\ServerSide\EventRequest;
use FacebookAds\Object\ServerSide\EventResponse;

class Events extends Component
{
    const STATUS_SUCCESS = 'success';
    const STATUS_ERROR = 'error';

    public function logEvent(EventRequest $request, EventResponse $response = null, string $error = null): Event
    {
        $facebookEvent = $this->_getEventData($request);

        $event = new Event();
        $event->event_id = $facebookEvent['event_id'];
        $event->event_name = $facebookEvent['event_name'];
        $event->event_data = $this->_toArray($facebookEvent);
        $event->status = $this->_getStatus($response, $error);
        $event->response = $this->_toArray($response);
        $event->error = $error;
        $event->created_at = new \DateTime();

        $event->save();

        return $event;
    }

    public function getEventsQuery(): ActiveQuery
    {
        return Event::find()->orderBy(['created_at' => SORT_DESC]);
    }

    public function getEvents(int $limit = 50, int $offset = 0): array
    {
        return $this
            ->getEventsQuery($limit, $offset)
            ->limit($limit)
            ->offset($offset)
            ->all();
    }

    public function getEventsPaginatedQuery(int $pageNum, int $pageSize = 50): Paginator
    {
        $query = $this->getEventsQuery($pageSize, $pageNum);

        return new Paginator((clone $query)->limit(null), [
            'currentPage' => $pageNum,
            'pageSize' => $pageSize,
        ]);
    }

    public function cleanup(): void
    {
        Event::deleteAll(['<', 'created_at', (new \DateTime('-1 month'))->format('Y-m-d H:i:s')]);
    }

    public function getTotalEvents(): int
    {
        return Event::find()->count();
    }


    /**
     * Private methods
     */

    private function _getEventData(EventRequest $request): FacebookEvent
    {
        return $request->getEvents()[0];
    }

    private function _getStatus(EventResponse $response = null, string $error = null): string
    {
        if ($error) {
            return self::STATUS_ERROR;
        }

        if ($response === null) {
            return self::STATUS_ERROR;
        }

        if ($response['events_received'] === 0) {
            return self::STATUS_ERROR;
        }

        if (count($response['messages']) > 0) {
            return self::STATUS_ERROR;
        }

        return self::STATUS_SUCCESS;
    }

    private function _toArray(?ArrayAccess $object): ?array
    {
        if ($object === null) {
            return null;
        }

        $array = [];

        foreach ($object->getters() as $attribute => $getter) {
            if (!method_exists($object, $getter)) {
                continue;
            }

            $value = $object->$getter();

            if (is_object($value)) {
                $value = $this->_toArray($value);
            }

            $array[$attribute] = $value;
        }

        return $array;
    }
}
