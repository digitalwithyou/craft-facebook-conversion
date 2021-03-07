<?php

namespace dwy\FacebookConversion\services;

use Craft;
use dwy\FacebookConversion\Plugin;
use FacebookAds\Api;
use FacebookAds\Object\ServerSide\Event;
use FacebookAds\Object\ServerSide\EventRequest;
use FacebookAds\Object\ServerSide\UserData;
use FacebookAds\Object\ServerSide\CustomData;

class FacebookBusinessApi
{
    protected $api;

    public function __construct()
    {
        $settings = Plugin::getInstance()->getSettings();

        $this->api = Api::init(null, null, $settings->getAccessToken());
    }

    public function sendEvent($eventName, UserData $userData = null, CustomData $customData = null)
    {
        $settings = Plugin::getInstance()->getSettings();
        $request = Craft::$app->getRequest();

        if (!$userData) {
            $userData = $this->getUserData();
        }

        $event = (new Event())
            ->setEventName($eventName)
            ->setEventTime(time())
            ->setEventSourceUrl($request->getAbsoluteUrl())
            ->setActionSource('website')
            ->setUserData($userData)
            ->setCustomData($customData);

        $eventRequest = (new EventRequest($settings->getPixelId()))
            ->setEvents([$event]);

        if (!empty($settings->testEventCode)) {
            $eventRequest->setTestEventCode($settings->testEventCode);
        }

        $eventRequest->execute();
    }

    public function getUserData(): UserData
    {
        $request = Craft::$app->getRequest();

        $userData = (new UserData())
            ->setFbc($this->getFbc())
            ->setFbp($this->getFbp())
            ->setClientIpAddress($request->getUserIp())
            ->setClientUserAgent($request->getUserAgent());

        return $userData;
    }

    public function getFbc(): ?string
    {
        $cookies = Craft::$app->getRequest()->cookies;
        $session = Craft::$app->session;

        $id = $session->get('fbc') ?? $cookies->get('_fbc');

        if (empty($id)) {
            return null;
        }

        $time = time();

        return "fb.1.$time.$id";
    }

    public function getFbp()
    {
        $settings = Plugin::getInstance()->getSettings();

        $pixelId = $settings->getPixelId();

        if (empty($pixelId)) {
            return null;
        }

        $time = time();

        return "fb.1.$time.$pixelId";
    }
}
