<?php

namespace dwy\FacebookConversion\listeners\commerce;

use craft\commerce\Plugin as Commerce;
use craft\commerce\models\Customer;
use dwy\FacebookConversion\Plugin;
use FacebookAds\Object\ServerSide\UserData;
use FacebookAds\Object\ServerSide\CustomData;

class BaseCommerceEvent
{
    public function sendEvent($eventName, Customer $customer = null, CustomData $customData = null)
    {
        if (!$customer) {
            $customer = Commerce::getInstance()
                ->getCarts()
                ->getCart()
                ->getCustomer();
        }

        $userData = $this->getUserData($customer);

        Plugin::getInstance()->facebook->sendEvent($eventName, $userData, $customData);
    }

    public function getUserData(Customer $customer): UserData
    {
        $userData = Plugin::getInstance()->facebook->getUserData();
        $cart = Commerce::getInstance()->getCarts()->getCart();

        $userData->setEmail($customer->getEmail());
        $userData->setExternalId($customer->id);

        $billingAddress = $cart->getBillingAddress() ?? $customer->getPrimaryBillingAddress();

        if ($billingAddress) {
            $userData
                ->setFirstName($billingAddress->firstName)
                ->setLastName($billingAddress->lastName)
                ->setPhone($billingAddress->phone)
                ->setState($billingAddress->stateName)
                ->setZipCode($billingAddress->zipCode)
                ->setCountryCode($this->getCountryIso($billingAddress));
        }

        return $userData;
    }

    private function getCountryIso($billingAddress): string
    {
        $country = $billingAddress->getCountry();

        return $country ? strtolower($country->iso) : '';
    }
}
