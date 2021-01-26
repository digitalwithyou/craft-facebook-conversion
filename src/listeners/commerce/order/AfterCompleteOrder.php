<?php

namespace dwy\FacebookConversion\listeners\commerce\order;

use dwy\FacebookConversion\listeners\commerce\BaseCommerceEvent;
use FacebookAds\Object\ServerSide\Content;
use FacebookAds\Object\ServerSide\CustomData;
use yii\base\Event;

class AfterCompleteOrder extends BaseCommerceEvent
{
    public function __invoke(Event $event)
    {
        $order = $event->sender;
        $contents = [];

        foreach ($order->getLineItems() as $lineItem) {
            $contents[] = (new Content())
                ->setItemPrice($lineItem->getSalePrice())
                ->setProductId($lineItem->getSku())
                ->setQuantity($lineItem->qty);
        }

        $customData = (new CustomData())
            ->setCurrency($order->getPaymentCurrency())
            ->setValue($order->getTotalPrice())
            ->setContentType('product')
            ->setContents($contents);

        $this->sendEvent('Purchase', $order->getCustomer(), $customData);
    }
}
