<?php

namespace dwy\FacebookConversion\listeners\commerce\cms;

use dwy\FacebookConversion\listeners\commerce\BaseCommerceEvent;
use FacebookAds\Object\ServerSide\CustomData;
use yii\base\Event;

class Search extends BaseCommerceEvent
{
    public function __invoke(Event $event)
    {
        $customData = (new CustomData())
            ->setSearchString($event->query->getQuery());

        $this->sendEvent('Search', null, $customData);
    }
}
