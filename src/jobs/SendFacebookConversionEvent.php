<?php

namespace dwy\FacebookConversion\jobs;

use Craft;
use craft\queue\BaseJob;
use dwy\FacebookConversion\Plugin;
use FacebookAds\Object\ServerSide\Content;
use FacebookAds\Object\ServerSide\CustomData;
use FacebookAds\Object\ServerSide\Event;
use FacebookAds\Object\ServerSide\EventRequest;
use FacebookAds\Object\ServerSide\UserData;
use Throwable;

class SendFacebookConversionEvent extends BaseJob
{
    /**
     * PHP-serialized {@see EventRequest} (queue table is trusted server-side storage).
     *
     * @var string
     */
    public $eventRequestSerialization = '';

    public function execute($queue): void
    {
        Plugin::getInstance()->facebook;

        $eventRequest = unserialize($this->eventRequestSerialization, [
            'allowed_classes' => [
                EventRequest::class,
                Event::class,
                UserData::class,
                CustomData::class,
                Content::class,
            ],
        ]);
        if (!$eventRequest instanceof EventRequest) {
            Craft::error('Facebook Conversion: Invalid queued event request payload.', __METHOD__);

            return;
        }

        try {
            $response = $eventRequest->execute();
            Plugin::getInstance()->events->logEvent($eventRequest, $response);
        } catch (Throwable $e) {
            Craft::error($e->getMessage(), __METHOD__);
            try {
                Plugin::getInstance()->events->logEvent($eventRequest, null, $e->getMessage());
            } catch (Throwable $logError) {
                Craft::error($logError->getMessage(), __METHOD__);
            }
        }
    }

    protected function defaultDescription(): string
    {
        return Craft::t('facebook-conversion', 'Send Facebook Conversion API event');
    }
}
