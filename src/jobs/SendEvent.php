<?php

namespace dwy\FacebookConversion\jobs;

use craft\queue\BaseJob;
use dwy\FacebookConversion\services\FacebookBusinessApi;
use FacebookAds\Object\ServerSide\Event;

class SendEvent extends BaseJob
{
    /** @var Event */
    public $event;
    /** @var string */
    public $pixelId;
    /** @var string|null */
    public $testEventCode;

    public function execute($queue): void
    {
        (new FacebookBusinessApi())->executeEvent($this->event, $this->pixelId, $this->testEventCode);
    }

    protected function defaultDescription(): ?string
    {
        return 'Send Facebook conversion event';
    }
}
