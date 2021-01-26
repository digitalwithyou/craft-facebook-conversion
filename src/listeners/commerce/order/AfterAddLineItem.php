<?php

namespace dwy\FacebookConversion\listeners\commerce\order;

use craft\commerce\events\LineItemEvent;
use dwy\FacebookConversion\listeners\commerce\BaseCommerceEvent;
use FacebookAds\Object\ServerSide\Content;
use FacebookAds\Object\ServerSide\CustomData;

class AfterAddLineItem extends BaseCommerceEvent
{
    public function __invoke(LineItemEvent $event)
    {
        $lineItem = $event->lineItem;
        $order = $lineItem->getOrder();

        $content = (new Content())
            ->setItemPrice($lineItem->getSalePrice())
            ->setProductId($lineItem->getSku())
            ->setQuantity($lineItem->qty);

        $customData = (new CustomData())
            ->setCurrency($order->getPaymentCurrency())
            ->setValue($lineItem->getTotal())
            ->setContentType('product')
            ->setContents([$content]);

        $this->sendEvent('AddToCart', $order->getCustomer(), $customData);
    }
}
