<?php

namespace dwy\FacebookConversion\listeners\cms;

use Craft;
use dwy\FacebookConversion\Plugin;
use FacebookAds\Object\ServerSide\CustomData;
use yii\base\Event;

class Search
{
    public function __invoke(Event $event)
    {
        $customData = (new CustomData())
            ->setSearchString($event->query->getQuery());

        Plugin::getInstance()->facebook->sendEvent('Search', null, $customData);
    }
}
