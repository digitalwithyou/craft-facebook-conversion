<?php

namespace dwy\FacebookConversion\listeners\commerce;

use craft\commerce\Plugin as Commerce;
use craft\commerce\elements\Order;
use dwy\FacebookConversion\Plugin;
use FacebookAds\Object\ServerSide\UserData;
use FacebookAds\Object\ServerSide\CustomData;

class BaseCommerceEvent
{
    // @phpstan-ignore-next-line
    public function sendEvent($eventName, Order $order = null, CustomData $customData = null)
    {
        if (!$order) {
            // @phpstan-ignore-next-line
            $order = Commerce::getInstance()
                ->getCarts()
                ->getCart();
        }

        if (!$customData) {
            $customData = new CustomData();
        }

        $userData = $this->getUserData($order);
        $customData->setOrderId($order->number);

        Plugin::getInstance()->facebook->sendEvent($eventName, $userData, $customData);
    }

    // @phpstan-ignore-next-line
    public function getUserData(Order $order): UserData
    {
        $plugin = Plugin::getInstance();
        $userData = $plugin->facebook->getUserData();
        // @phpstan-ignore-next-line
        $customer = $order->getCustomer() ?? Commerce::getInstance()->getCarts()->getCart()->getCustomer();
        $email = $order->getEmail() ?? $customer->getEmail();

        if (!empty($email)) {
            $userData->setEmail($email);

            $externalId = $plugin->getExternalId($email);
            $userData->setExternalId($externalId);
        }

        $address = $order->getBillingAddress() ?? $customer->getPrimaryBillingAddress();

        if (!$address) {
            $address = $order->getShippingAddress() ?? $customer->getPrimaryShippingAddress();
        }

        if ($address) {
            $userData
                ->setFirstName($address->firstName)
                ->setLastName($address->lastName)
                ->setPhone($address->phone)
                ->setState($address->stateName)
                ->setCity($address->city)
                ->setZipCode($address->zipCode)
                ->setCountryCode($this->getCountryIso($address));
        }

        if ($order->lastIp) {
            $userData->setClientIpAddress($order->lastIp);
        }

        return $userData;
    }

    private function getCountryIso($billingAddress): string
    {
        $country = $billingAddress->getCountry();

        return $country ? strtolower($country->iso) : '';
    }
}
