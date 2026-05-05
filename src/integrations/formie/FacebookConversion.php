<?php

namespace dwy\FacebookConversion\integrations\formie;

use Craft;
use craft\helpers\App;
use dwy\FacebookConversion\Plugin;
use FacebookAds\Object\ServerSide\CustomData;
use FacebookAds\Object\ServerSide\UserData;
use Throwable;
use verbb\formie\base\Integration;
use verbb\formie\base\Miscellaneous;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\models\IntegrationField;
use verbb\formie\models\IntegrationFormSettings;
use verbb\formie\models\Stencil;

/**
 * Sends a Facebook Conversions API server event for each Formie submission.
 *
 * Field mapping is split into two tables:
 *   - User Data: PII the FB SDK hashes (email, phone, name, address, etc.)
 *   - Custom Data: not hashed (value, currency, content_name, etc.)
 *
 * The actual send is delegated to FacebookBusinessApi::sendEvent() so events
 * flow through the existing async queue, get logged, and appear in the
 * "Facebook Conversion Events" CP utility just like Commerce events.
 */
class FacebookConversion extends Miscellaneous
{
    public const EVENT_TYPE_CUSTOM = 'Custom';

    public ?string $eventType = null;
    public ?string $customEventName = null;
    public ?array $userDataFieldMapping = null;
    public ?array $customDataFieldMapping = null;

    public static function displayName(): string
    {
        return Craft::t('facebook-conversion', 'Facebook Conversion');
    }

    public static function supportsConnection(): bool
    {
        return false;
    }

    /**
     * All FB standard events from the Meta Pixel reference, plus "Custom"
     * which reveals a free-text field for non-standard event names.
     */
    public static function getStandardEventTypes(): array
    {
        return [...StandardEvents::names(), self::EVENT_TYPE_CUSTOM];
    }

    public function getDescription(): string
    {
        return Craft::t('facebook-conversion', 'Send a Facebook Conversions API server event when a form is submitted. Configure the Pixel ID and Access Token under Settings → Plugins → Facebook Conversion.');
    }

    public function getSettingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate(
            'facebook-conversion/integrations/formie/_plugin-settings',
            $this->getSettingsHtmlVariables()
        );
    }

    public function getFormSettingsHtml(Form|Stencil $form): string
    {
        return Craft::$app->getView()->renderTemplate(
            'facebook-conversion/integrations/formie/_form-settings',
            $this->getFormSettingsHtmlVariables($form)
        );
    }

    public function getIconUrl(): string
    {
        [, $url] = Craft::$app->getAssetManager()->publish('@dwy/FacebookConversion/icon.svg');
        return $url;
    }

    public function fetchFormSettings(): IntegrationFormSettings
    {
        return new IntegrationFormSettings([
            'eventTypes' => self::getStandardEventTypes(),
            'eventMeta' => StandardEvents::all(),
            'userDataFields' => $this->getUserDataFields(),
            'customDataFields' => $this->getCustomDataFields(),
        ]);
    }

    /**
     * Always build settings fresh. There's no remote API to call, so the
     * Formie cache (populated only on explicit "Refresh") would be empty
     * on first form-edit load.
     */
    public function getFormSettings(bool $useCache = true): bool|IntegrationFormSettings
    {
        return $this->fetchFormSettings();
    }

    public function sendPayload(Submission $submission): bool
    {
        try {
            $eventName = $this->resolveEventName();

            if (!$eventName) {
                Integration::error($this, Craft::t('facebook-conversion', 'No event type configured.'));
                return false;
            }

            $userData = $this->buildUserData($submission);
            $customData = $this->buildCustomData($submission);
            // Submission UID becomes the FB event_id so a browser-side Pixel firing
            // the same event can dedup against this server event in Meta's reporting.
            $eventId = $submission->uid ?: null;
            $eventSourceUrl = ClientDataCapture::restoreSourceUrl($submission);

            Plugin::getInstance()->facebook->sendEvent(
                $eventName,
                $userData,
                $customData,
                $eventId,
                $eventSourceUrl
            );
        } catch (Throwable $e) {
            Integration::apiError($this, $e, false);
            return false;
        }

        return true;
    }

    public function getUserDataFields(): array
    {
        $t = fn(string $s) => Craft::t('facebook-conversion', $s);
        $field = fn(string $handle, string $name, string $description, array $extra = []) => new IntegrationField(array_merge([
            'handle' => $handle,
            'name' => $t($name),
            'data' => ['description' => $t($description)],
        ], $extra));

        return [
            $field('email', 'Email',
                'Email address. The Facebook SDK will lowercase and SHA-256 hash this value before sending.'),
            $field('phone', 'Phone',
                'Phone number. Include country code, area code, and number, digits only with no symbols. Hashed by the SDK.',
                ['type' => IntegrationField::TYPE_PHONE]),
            $field('firstName', 'First Name', 'First name. Hashed by the SDK.'),
            $field('lastName', 'Last Name', 'Last name. Hashed by the SDK.'),
            $field('dateOfBirth', 'Date of Birth',
                'Date of birth. Map a Formie Date field. Values are converted to YYYYMMDD automatically (e.g. 19850514). Hashed by the SDK.',
                ['type' => IntegrationField::TYPE_DATE]),
            $field('gender', 'Gender (m/f)',
                'Gender as a single letter, "m" for male or "f" for female. Hashed by the SDK.'),
            $field('city', 'City', 'City of the user, lowercase, no spaces. Hashed by the SDK.'),
            $field('state', 'State',
                'State / region. Two-letter code preferred (e.g. "ca" for California). Hashed by the SDK.'),
            $field('zipCode', 'Zip Code', 'Postal / zip code. Hashed by the SDK.'),
            $field('countryCode', 'Country Code',
                'Two-letter ISO 3166-1 alpha-2 country code (e.g. "us", "be"). Hashed by the SDK.'),
            $field('externalId', 'External ID',
                'A unique identifier for the user from your own systems (e.g. customer ID, loyalty number). Hashed by the SDK. If left empty, the plugin derives one from the email.'),
        ];
    }

    public function getCustomDataFields(): array
    {
        $t = fn(string $s) => Craft::t('facebook-conversion', $s);
        $field = fn(string $handle, string $name, string $description, array $extra = []) => new IntegrationField(array_merge([
            'handle' => $handle,
            'name' => $t($name),
            'data' => ['description' => $t($description)],
        ], $extra));

        return [
            $field('value', 'Value',
                'Monetary value associated with the event (e.g. order total). Used with currency.',
                ['type' => IntegrationField::TYPE_FLOAT]),
            $field('currency', 'Currency',
                'Three-letter ISO 4217 currency code (e.g. "USD", "EUR"). Required when Value is sent.'),
            $field('contentName', 'Content Name',
                'Name of the page or product associated with the event (e.g. "ABC Leather Sandal").'),
            $field('contentCategory', 'Content Category',
                'Category of the page or product (e.g. "Apparel & Accessories > Shoes").'),
            $field('contentIds', 'Content IDs',
                'One or more product IDs (SKUs). Comma-separated for multiple values. Match the IDs used in your product catalog.'),
            $field('contentType', 'Content Type',
                'Either "product" or "product_group". Use "product" when sending IDs of individual SKUs, "product_group" for parent IDs.'),
            $field('numItems', 'Number of Items',
                'Number of items the user interacts with. Used with InitiateCheckout, AddToCart, Purchase events.',
                ['type' => IntegrationField::TYPE_NUMBER]),
            $field('orderId', 'Order ID',
                'Unique transaction identifier (e.g. order number). Used with Purchase events for deduplication and reporting.'),
            $field('predictedLtv', 'Predicted LTV',
                'Predicted lifetime value of the user (e.g. expected revenue from a subscriber). Used with Subscribe and StartTrial events.',
                ['type' => IntegrationField::TYPE_FLOAT]),
            $field('deliveryCategory', 'Delivery Category',
                'Type of delivery for a Purchase event. One of "in_store", "curbside", "home_delivery".'),
            $field('searchString', 'Search String',
                'The string the user searched for. Used only with Search events.'),
            $field('status', 'Status',
                'Status of the registration. Used only with CompleteRegistration events (e.g. "complete", "pending").'),
        ];
    }

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['eventType'], 'required', 'on' => [Integration::SCENARIO_FORM]];

        $rules[] = [['customEventName'], 'required', 'when' => function($model) {
            return $model->eventType === self::EVENT_TYPE_CUSTOM;
        }, 'on' => [Integration::SCENARIO_FORM]];

        $rules[] = [
            ['userDataFieldMapping'], 'validateFieldMapping',
            'params' => $this->getUserDataFields(),
            'when' => function($model) {
                return $model->enabled;
            },
            'on' => [Integration::SCENARIO_FORM],
        ];

        $rules[] = [
            ['customDataFieldMapping'], 'validateFieldMapping',
            'params' => $this->getCustomDataFields(),
            'when' => function($model) {
                return $model->enabled;
            },
            'on' => [Integration::SCENARIO_FORM],
        ];

        $rules[] = [
            ['customDataFieldMapping'], 'validateRequiredCustomDataForEvent',
            'when' => function($model) {
                return $model->enabled;
            },
            'on' => [Integration::SCENARIO_FORM],
        ];

        return $rules;
    }

    /**
     * Some FB events have hard-required custom_data parameters per the Pixel
     * reference (only Purchase as of v1: requires currency + value). When the
     * user picks such an event, force them to map those fields.
     */
    public function validateRequiredCustomDataForEvent(string $attribute): void
    {
        $event = StandardEvents::find((string)$this->eventType);
        if (!$event || empty($event['required'])) {
            return;
        }

        $mapping = is_array($this->customDataFieldMapping) ? $this->customDataFieldMapping : [];
        $fields = $this->getCustomDataFields();

        foreach ($event['required'] as $handle) {
            if (empty($mapping[$handle])) {
                $name = $handle;
                foreach ($fields as $field) {
                    if ($field->handle === $handle) {
                        $name = $field->name;
                        break;
                    }
                }
                $this->addError($attribute, Craft::t('facebook-conversion', '{name} must be mapped for {event} events.', [
                    'name' => $name,
                    'event' => $event['name'],
                ]));
            }
        }
    }

    private function resolveEventName(): ?string
    {
        if ($this->eventType === self::EVENT_TYPE_CUSTOM) {
            $name = trim((string)App::parseEnv($this->customEventName));
            return $name !== '' ? $name : null;
        }

        return $this->eventType ?: null;
    }

    private function buildUserData(Submission $submission): UserData
    {
        // Prefer the snapshot captured at submit time (survives Formie's queue);
        // fall back to live request data when no snapshot is available.
        $userData = ClientDataCapture::restore($submission)
            ?? Plugin::getInstance()->facebook->getUserData();

        $values = $this->getFieldMappingValues(
            $submission,
            $this->userDataFieldMapping,
            $this->getUserDataFields()
        );

        foreach ($values as $handle => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            if ($handle === 'countryCode' && is_string($value)) {
                $value = strtolower($value);
            }

            if ($handle === 'dateOfBirth') {
                $value = $this->normalizeDateOfBirth($value);
                if ($value === null) {
                    continue;
                }
            }

            $setter = 'set' . ucfirst($handle);
            if (method_exists($userData, $setter)) {
                $userData->$setter($value);
            }
        }

        $email = $values['email'] ?? null;
        $externalId = $values['externalId'] ?? null;
        if (!empty($email) && empty($externalId)) {
            $userData->setExternalId(Plugin::getInstance()->getExternalId($email));
        }

        return $userData;
    }

    /**
     * Coerce whatever the user mapped (DateTime, Y-m-d string, raw YYYYMMDD, etc.)
     * into the YYYYMMDD format Facebook's UserData expects for date_of_birth.
     */
    private function normalizeDateOfBirth(mixed $value): ?string
    {
        if (is_string($value) && preg_match('/^\d{8}$/', $value)) {
            return $value;
        }

        $date = \craft\helpers\DateTimeHelper::toDateTime($value);
        return $date ? $date->format('Ymd') : null;
    }

    private function buildCustomData(Submission $submission): ?CustomData
    {
        $values = $this->getFieldMappingValues(
            $submission,
            $this->customDataFieldMapping,
            $this->getCustomDataFields()
        );

        $hasValue = false;
        foreach ($values as $value) {
            if ($value !== null && $value !== '') {
                $hasValue = true;
                break;
            }
        }

        if (!$hasValue) {
            return null;
        }

        $customData = new CustomData();

        foreach ($values as $handle => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            $setter = 'set' . ucfirst($handle);
            if (method_exists($customData, $setter)) {
                $customData->$setter($value);
            }
        }

        return $customData;
    }
}
