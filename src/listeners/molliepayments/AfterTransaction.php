<?php

namespace dwy\FacebookConversion\listeners\molliepayments;

use dwy\FacebookConversion\Plugin;
use FacebookAds\Object\ServerSide\CustomData;
use studioespresso\molliepayments\events\TransactionUpdateEvent;

class AfterTransaction
{
    public function __invoke(TransactionUpdateEvent $event)
    {
        if ($event->status !== 'paid') {
            return;
        }

        $userData = Plugin::getInstance()->facebook->getUserData();
        $userData->setEmail($event->payment->email);

        if (isset($event->payment->firstName)) {
            $userData->setFirstName($event->payment->firstName);
        }

        if (isset($event->payment->lastName)) {
            $userData->setLastName($event->payment->lastName);
        }

        $customData = (new CustomData())
            ->setCurrency($event->transaction->currency)
            ->setValue($event->transaction->amount);


        Plugin::getInstance()->facebook->sendEvent('Purchase', $userData, $customData);
    }
}
