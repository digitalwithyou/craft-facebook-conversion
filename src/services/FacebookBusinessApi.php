<?php

namespace dwy\FacebookConversion\services;

use Craft;
use dwy\FacebookConversion\logger\CraftLogger;
use dwy\FacebookConversion\Plugin;
use FacebookAds\Api;
use FacebookAds\Exception\Exception as FacebookException;
use FacebookAds\Object\ServerSide\CustomData;
use FacebookAds\Object\ServerSide\Event;
use FacebookAds\Object\ServerSide\EventRequest;
use FacebookAds\Object\ServerSide\UserData;

class FacebookBusinessApi
{
    protected $api;

    public function __construct()
    {
        $settings = Plugin::getInstance()->getSettings();

        $this->api = Api::init(null, null, $settings->getAccessToken());

        $logger = new CraftLogger();

        $this->api->setLogger($logger);
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

        $testEventCode = $settings->getTestEventCode();
        if (!empty($testEventCode)) {
            $eventRequest->setTestEventCode($testEventCode);
        }

        try {
            $eventRequest->execute();
        } catch (FacebookException $exception) {
            Craft::error($exception->getMessage());
        }
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
        $session = Craft::$app->has('session', true) ? Craft::$app->get('session') : null;

        if (!$session || !$session->getIsActive()) {
            return null;
        }

        $fbc = $session->get('fbc');

        if (empty($fbc) && isset($_COOKIE['_fbc']) && preg_match('/fb\.1\.\d+\.\S+/', $_COOKIE['_fbc'])) {
            $fbc = $_COOKIE['_fbc'];
        }

        return $fbc;
    }

    public function getFbp(): ?string
    {
        $session = Craft::$app->has('session', true) ? Craft::$app->get('session') : null;

        if (!$session || !$session->getIsActive()) {
            return null;
        }

        $fbp = isset($_COOKIE['_fbp']) ? $_COOKIE['_fbp'] : '';

        if (empty($fbp) || !preg_match('/fb\.1\.\d+\.\d+/', $fbp)) {
            $fbp = $session->get('_fbp');
        }

        if (empty($fbp)) {
            $time = time();
            $randomNumber = random_int(1000000000, 9999999999);
            $fbp = "fb.1.$time.$randomNumber";

            $this->setCookie('_fbp', $fbp);

            $session->set('_fbp', $fbp);
        }

        return $fbp;
    }

    private function setCookie($name = '', $value = '', $expire = 2147483647): void
    {
        $domain = Craft::$app->getConfig()->getGeneral()->defaultCookieDomain;
        $expire = (int) $expire;

        if (PHP_VERSION_ID >= 70300) {
            setcookie($name, $value, [
                'expires' => $expire,
                'path' => '/',
                'domain' => $domain,
            ]);
        } else {
            setcookie($name, $value, $expire, '/', $domain);
        }

        $_COOKIE[$name] = $value;
    }
}
