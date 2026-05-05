<?php

namespace dwy\FacebookConversion\integrations\commerce\cms;

use dwy\FacebookConversion\integrations\commerce\BaseCommerceEvent;
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
