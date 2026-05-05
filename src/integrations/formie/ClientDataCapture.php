<?php

namespace dwy\FacebookConversion\integrations\formie;

use Craft;
use dwy\FacebookConversion\Plugin;
use FacebookAds\Object\ServerSide\UserData;
use Throwable;
use verbb\formie\elements\Submission;
use verbb\formie\events\SubmissionEvent;

/**
 * Captures _fbc / _fbp / IP / user-agent / source URL from the live request
 * the moment a Formie submission is saved. Runs synchronously, in-request,
 * before Formie may push the integration into a queue job where cookies and
 * request data are gone.
 *
 * Stored in the cache keyed by submission UID with a 1-hour TTL. The
 * FacebookConversion integration restores it inside sendPayload().
 */
class ClientDataCapture
{
    public const CACHE_TTL = 3600;
    public const CACHE_PREFIX = 'facebook-conversion:formie:client:';

    public function handle(SubmissionEvent $event): void
    {
        try {
            /** @var Submission $submission */
            $submission = $event->submission;

            if (!$submission || !$submission->uid) {
                return;
            }

            // Skip spam submissions; the integration won't run for them anyway,
            // so writing the cache entry is just noise.
            if ($submission->isSpam) {
                return;
            }

            // Don't snapshot from CP/console; only real front-end submissions.
            if (Craft::$app->getRequest()->getIsCpRequest() || Craft::$app->getRequest()->getIsConsoleRequest()) {
                return;
            }

            $facebook = Plugin::getInstance()->facebook;
            $request = Craft::$app->getRequest();

            $snapshot = [
                'fbc' => $facebook->getFbc(),
                'fbp' => $facebook->getFbp(),
                'ip' => $request->getUserIp(),
                'userAgent' => $request->getUserAgent(),
                'sourceUrl' => $request->getReferrer() ?: $request->getAbsoluteUrl(),
            ];

            Craft::$app->getCache()->set(
                self::cacheKey($submission->uid),
                $snapshot,
                self::CACHE_TTL
            );
        } catch (Throwable $e) {
            Craft::warning('Failed to capture client data for Facebook Conversion: ' . $e->getMessage(), __METHOD__);
        }
    }

    /**
     * Restore the snapshot into a UserData object. Returns null when no
     * snapshot is available (caller falls back to live request data).
     * Consumes the cache entry on read.
     */
    public static function restore(Submission $submission): ?UserData
    {
        $snapshot = self::pop($submission);
        if (!$snapshot) {
            return null;
        }

        $userData = new UserData();

        if (!empty($snapshot['fbc'])) {
            $userData->setFbc($snapshot['fbc']);
        }
        if (!empty($snapshot['fbp'])) {
            $userData->setFbp($snapshot['fbp']);
        }
        if (!empty($snapshot['ip'])) {
            $userData->setClientIpAddress($snapshot['ip']);
        }
        if (!empty($snapshot['userAgent'])) {
            $userData->setClientUserAgent($snapshot['userAgent']);
        }

        // Stash the source URL on the cache for the caller to pick up via restoreSourceUrl().
        if (!empty($snapshot['sourceUrl'])) {
            Craft::$app->getCache()->set(
                self::cacheKey($submission->uid) . ':sourceUrl',
                $snapshot['sourceUrl'],
                self::CACHE_TTL
            );
        }

        return $userData;
    }

    public static function restoreSourceUrl(Submission $submission): ?string
    {
        $cache = Craft::$app->getCache();
        $key = self::cacheKey($submission->uid) . ':sourceUrl';
        $url = $cache->get($key);
        if ($url) {
            $cache->delete($key);
            return (string)$url;
        }
        return null;
    }

    private static function pop(Submission $submission): ?array
    {
        $cache = Craft::$app->getCache();
        $key = self::cacheKey($submission->uid);
        $snapshot = $cache->get($key);

        if (!is_array($snapshot)) {
            return null;
        }

        $cache->delete($key);

        return $snapshot;
    }

    private static function cacheKey(string $uid): string
    {
        return self::CACHE_PREFIX . $uid;
    }
}
