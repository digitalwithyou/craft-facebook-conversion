<?php

namespace dwy\FacebookConversion\listeners\commerce;

use craft\commerce\elements\Order;
use craft\commerce\Plugin as Commerce;
use dwy\FacebookConversion\Plugin;
use FacebookAds\Object\ServerSide\CustomData;
use FacebookAds\Object\ServerSide\UserData;

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
        $email = $order->getEmail();

        if (empty($email) && $customer) {
            $email = $customer->email;
        }

        if (!empty($email)) {
            $userData->setEmail($email);

            $externalId = $plugin->getExternalId($email);
            $userData->setExternalId($externalId);
        }

        $address = $order->getBillingAddress();

        if (!$address) {
            $address = $order->getShippingAddress();
        }

        if ($address) {
            // Craft 3 & 4 compatibility
            $phone = property_exists($address, 'phone') ?? $address->phone;
            $city = property_exists($address, 'city') ? $address->city : $address->getLocality();
            $zipCode = property_exists($address, 'zipCode') ? $address->zipCode : $address->getPostalCode();
            $state = property_exists($address, 'stateName') ? $address->stateName : $address->administrativeArea;
            $country = method_exists($address, 'getCountryIso') ? $address->getCountryIso() : $address->getCountryCode();

            $userData
                ->setFirstName($address->firstName)
                ->setLastName($address->lastName)
                ->setPhone($phone)
                ->setState($state)
                ->setCity($city)
                ->setZipCode($zipCode)
                ->setCountryCode(strtolower($country));
        }

        if ($order->lastIp) {
            $userData->setClientIpAddress($order->lastIp);
        }

        return $userData;
    }
}
