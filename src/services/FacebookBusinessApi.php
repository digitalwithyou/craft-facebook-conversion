<?php

namespace dwy\FacebookConversion\services;

use Craft;
use dwy\FacebookConversion\jobs\SendFacebookConversionEvent;
use dwy\FacebookConversion\logger\CraftLogger;
use dwy\FacebookConversion\Plugin;
use FacebookAds\Api;
use FacebookAds\CrashReporter;
use FacebookAds\Http\Adapter\CurlAdapter;
use FacebookAds\Http\Client;
use FacebookAds\Object\ServerSide\CustomData;
use FacebookAds\Object\ServerSide\Event;
use FacebookAds\Object\ServerSide\EventRequest;
use FacebookAds\Object\ServerSide\UserData;
use FacebookAds\Session;
use Throwable;

/**
 * HTTP client timeouts: fail fast so queued work does not block workers excessively.
 * Connect 5s, total request 15s (SDK default total is 60s).
 */
class FacebookBusinessApi
{
    protected ?Api $api = null;

    public function __construct()
    {
        $settings = Plugin::getInstance()->getSettings();

        $session = new Session(null, null, $settings->getAccessToken());
        $client = new Client();
        $adapter = $client->getAdapter();
        if ($adapter instanceof CurlAdapter) {
            $opts = $adapter->getOpts();
            $opts[\CURLOPT_CONNECTTIMEOUT] = 5;
            $opts[\CURLOPT_TIMEOUT] = 15;
            $adapter->setOpts($opts);
        }

        $this->api = new Api($client, $session);
        Api::setInstance($this->api);
        CrashReporter::enable();

        $logger = new CraftLogger();
        $this->api->setLogger($logger);
    }

    public function sendEvent(string $eventName, ?UserData $userData = null, ?CustomData $customData = null, ?string $eventId = null, ?string $eventSourceUrl = null): void
    {
        $settings = Plugin::getInstance()->getSettings();
        $request = Craft::$app->getRequest();

        if (!$settings->getPixelId() || !$settings->getAccessToken()) {
            return;
        }

        if (!$userData) {
            $userData = $this->getUserData();
        }

        $event = (new Event())
            ->setEventName($eventName)
            ->setEventTime(time())
            ->setEventSourceUrl($eventSourceUrl ?? $request->getAbsoluteUrl())
            ->setActionSource('website')
            ->setUserData($userData)
            ->setCustomData($customData);

        if ($eventId) {
            $event->setEventId($eventId);
        }

        $eventRequest = (new EventRequest($settings->getPixelId()))
            ->setEvents([$event]);

        $testEventCode = $settings->getTestEventCode();
        if (!empty($testEventCode)) {
            $eventRequest->setTestEventCode($testEventCode);
        }

        try {
            Craft::$app->getQueue()->push(new SendFacebookConversionEvent([
                'eventRequestSerialization' => serialize($eventRequest),
            ]));
        } catch (Throwable $e) {
            Craft::error($e->getMessage(), __METHOD__);
            $this->_tryLogEventFailure($eventRequest, $e->getMessage());
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

        setcookie($name, $value, [
            'expires' => $expire,
            'path' => '/',
            'domain' => $domain,
            'secure' => Craft::$app->getRequest()->getIsSecureConnection(),
            'samesite' => 'Lax',
            // httponly intentionally OFF: _fbp must be readable by Meta's JS pixel.
        ]);

        $_COOKIE[$name] = $value;
    }

    private function _tryLogEventFailure(EventRequest $eventRequest, string $message): void
    {
        try {
            Plugin::getInstance()->events->logEvent($eventRequest, null, $message);
        } catch (Throwable $logError) {
            Craft::error($logError->getMessage(), __METHOD__);
        }
    }
}
