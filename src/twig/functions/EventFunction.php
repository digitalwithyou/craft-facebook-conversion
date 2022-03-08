<?php

namespace dwy\FacebookConversion\twig\functions;

use dwy\FacebookConversion\Plugin;
use FacebookAds\Object\ServerSide\CustomData;

class EventFunction
{
    public function __invoke($eventName, $eventId, $userData = [], $customData = []) : void
    {
        $userDataObject = Plugin::getInstance()->facebook->getUserData();
        $this->dataToObjects($userData, $userDataObject);

        $customDataObject = new CustomData();
        $this->dataToObjects($customData, $customDataObject);

        Plugin::getInstance()->facebook->sendEvent($eventName, $eventId, $userDataObject, $customDataObject);
    }

    private function dataToObjects($data, &$object)
    {
        foreach ($data as $key => $value) {
            $method = 'set' . str_replace('_', '', ucwords($key, " \t\r\n\f\v_"));

            if (method_exists($object, $method)) {
                $object->$method($value);
            }
        }
    }
}
