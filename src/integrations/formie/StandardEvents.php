<?php

namespace dwy\FacebookConversion\integrations\formie;

/**
 * Registry of Facebook standard events and the custom_data parameters
 * each one typically uses. Source: Meta Pixel reference
 * (https://developers.facebook.com/docs/meta-pixel/reference).
 *
 * Each entry has:
 *   - name:         the FB event name as sent to the Conversions API
 *   - recommended:  custom_data parameter handles the Pixel reference lists
 *                   under "Object Properties" for that event
 *   - required:     custom_data parameter handles FB's docs explicitly
 *                   require for that event (very few; only Purchase)
 *
 * Handles match the IntegrationField handles in FacebookConversion::getCustomDataFields().
 */
class StandardEvents
{
    /**
     * @return array<int, array{name: string, recommended: array<int, string>, required: array<int, string>}>
     */
    public static function all(): array
    {
        return [
            ['name' => 'AddPaymentInfo',       'recommended' => ['contentCategory', 'contentIds', 'currency', 'value'],                          'required' => []],
            ['name' => 'AddToCart',            'recommended' => ['contentIds', 'contentName', 'contentType', 'currency', 'value'],               'required' => []],
            ['name' => 'AddToWishlist',        'recommended' => ['contentName', 'contentCategory', 'contentIds', 'currency', 'value'],           'required' => []],
            ['name' => 'CompleteRegistration', 'recommended' => ['contentName', 'currency', 'status', 'value'],                                  'required' => []],
            ['name' => 'Contact',              'recommended' => [],                                                                              'required' => []],
            ['name' => 'CustomizeProduct',     'recommended' => [],                                                                              'required' => []],
            ['name' => 'Donate',               'recommended' => ['currency', 'value'],                                                           'required' => []],
            ['name' => 'FindLocation',         'recommended' => ['contentIds', 'contentType', 'contentName', 'contentCategory'],                 'required' => []],
            ['name' => 'InitiateCheckout',     'recommended' => ['contentCategory', 'contentIds', 'currency', 'numItems', 'value'],              'required' => []],
            ['name' => 'Lead',                 'recommended' => ['contentCategory', 'contentName', 'currency', 'value'],                         'required' => []],
            ['name' => 'Purchase',             'recommended' => ['contentIds', 'contentName', 'contentType', 'numItems'],                        'required' => ['currency', 'value']],
            ['name' => 'Schedule',             'recommended' => [],                                                                              'required' => []],
            ['name' => 'Search',               'recommended' => ['contentCategory', 'contentIds', 'currency', 'searchString', 'value'],          'required' => []],
            ['name' => 'StartTrial',           'recommended' => ['currency', 'predictedLtv', 'value'],                                           'required' => []],
            ['name' => 'SubmitApplication',    'recommended' => [],                                                                              'required' => []],
            ['name' => 'Subscribe',            'recommended' => ['currency', 'predictedLtv', 'value'],                                           'required' => []],
            ['name' => 'ViewContent',          'recommended' => ['contentIds', 'contentName', 'contentCategory', 'contentType', 'currency', 'value'], 'required' => []],
        ];
    }

    /**
     * @return array{name: string, recommended: array<int, string>, required: array<int, string>}|null
     */
    public static function find(string $name): ?array
    {
        foreach (self::all() as $event) {
            if ($event['name'] === $name) {
                return $event;
            }
        }
        return null;
    }

    /**
     * @return array<int, string>
     */
    public static function names(): array
    {
        return array_map(static fn(array $e) => $e['name'], self::all());
    }
}
