<?php

namespace dwy\FacebookConversion;

use Craft;
use craft\base\Model;
use craft\base\Plugin as BasePlugin;
use craft\commerce\elements\Order;
use craft\events\RegisterComponentTypesEvent;
use craft\services\Search;
use craft\services\Utilities;
use craft\web\Application;
use craft\web\WebApplication;
use dwy\FacebookConversion\console\controllers\EventsController;
use dwy\FacebookConversion\hooks\BodyTag;
use dwy\FacebookConversion\hooks\HeadTag;
use dwy\FacebookConversion\jobs\CleanupEventLogs;
use dwy\FacebookConversion\listeners\cms\Search as CmsSearchEvent;
use dwy\FacebookConversion\listeners\commerce\cms\Search as CommerceSearchEvent;
use dwy\FacebookConversion\listeners\commerce\order\AfterAddLineItem;
use dwy\FacebookConversion\listeners\commerce\order\AfterCompleteOrder;
use dwy\FacebookConversion\listeners\molliepayments\AfterTransaction;
use dwy\FacebookConversion\models\Settings;
use dwy\FacebookConversion\services\Events;
use dwy\FacebookConversion\services\FacebookBusinessApi;
use dwy\FacebookConversion\twig\TwigExtension;
use dwy\FacebookConversion\utilities\Events as EventsUtility;
use dwy\FacebookConversion\web\assets\FacebookConversionAsset;
use studioespresso\molliepayments\MolliePayments;
use studioespresso\molliepayments\services\Transaction;
use yii\base\Event;

class Plugin extends BasePlugin
{
    public function init()
    {
        $this->hasCpSettings = true;

        $this->_registerComponents();

        Event::on(Application::class, Application::EVENT_INIT, function() {
            parent::init();

            $this->_registerHooks();
            $this->_registerEventListeners();
            $this->_registerUtilities();

            if (Craft::$app->getRequest()->getIsConsoleRequest()) {
                return;
            }

            if (Craft::$app->getRequest()->getIsCpRequest()) {
                $this->_registerAssets();
            }

            $this->_registerTwigFunctions();
            $this->_getFacebookClickId();
        });
    }

    public function getExternalId($email = null)
    {
        return Craft::$app->getSecurity()->generatePasswordHash('fb_' . $email);
    }

    protected function createSettingsModel(): ?Model
    {
        return new Settings();
    }

    protected function settingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate(
            'facebook-conversion/settings',
            [ 'settings' => $this->getSettings() ]
        );
    }

    private function _registerHooks()
    {
        Craft::$app->view->hook('facebook-conversion-head-tag', new HeadTag());
        Craft::$app->view->hook('facebook-conversion-body-tag', new BodyTag());
    }

    private function _registerAssets()
    {
        Craft::$app->getView()->registerAssetBundle(FacebookConversionAsset::class);
    }

    private function _registerTwigFunctions()
    {
        Craft::$app->view->registerTwigExtension(new TwigExtension());
    }

    private function _registerEventListeners()
    {
        if (class_exists(Order::class)) {
            Event::on(Order::class, Order::EVENT_AFTER_ADD_LINE_ITEM, new AfterAddLineItem());
            Event::on(Order::class, Order::EVENT_AFTER_COMPLETE_ORDER, new AfterCompleteOrder());
            Event::on(Search::class, Search::EVENT_AFTER_SEARCH, new CommerceSearchEvent());
        } else {
            Event::on(Search::class, Search::EVENT_AFTER_SEARCH, new CmsSearchEvent());
        }

        if (class_exists(MolliePayments::class) && class_exists(Transaction::class)) {
            Event::on(Transaction::class, MolliePayments::EVENT_AFTER_TRANSACTION_UPDATE, new AfterTransaction());
        }
    }

    private function _registerComponents()
    {
        $this->setComponents([
            'facebook' => FacebookBusinessApi::class,
            'events' => Events::class,
        ]);
    }

    private function _registerUtilities()
    {
        $eventName = (Craft::$app->getVersion() >= '5.0')
            ? Utilities::EVENT_REGISTER_UTILITIES
            : Utilities::EVENT_REGISTER_UTILITY_TYPES;

        Event::on(
            Utilities::class,
            $eventName,
            function(RegisterComponentTypesEvent $event) {
                $event->types[] = EventsUtility::class;
            }
        );
    }

    private function _getFacebookClickId()
    {
        $session = Craft::$app->has('session', true) ? Craft::$app->get('session') : null;
        $fbclid = Craft::$app->getRequest()->get('fbclid');

        if ($fbclid && $session && $session->getIsActive()) {
            $time = time();
            $fbc = "fb.1.$time.$fbclid";

            $session->set('fbc', $fbc);
        }
    }

    private function _dailyCleanup()
    {
        // We don't want to run this in the console, queue or when running tests
        if (!(Craft::$app instanceof WebApplication)) {
            return;
        }

        Event::on(
            WebApplication::class,
            WebApplication::EVENT_AFTER_REQUEST,
            function () {
                $cache = Craft::$app->getCache();
                $key = 'facebook-conversion-daily-cleanup-last-run';

                if (!$cache->exists($key)) {
                    // Push job to queue
                    Craft::$app->queue->push(new CleanupEventLogs());

                    // Store timestamp for 24 hours
                    $cache->set($key, true, 86400);
                }
            }
        );
    }
}
