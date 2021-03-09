<?php

namespace dwy\FacebookConversion;

use Craft;
use craft\base\Plugin as BasePlugin;
use craft\commerce\elements\Order;
use craft\services\Search;
use craft\web\Application;
use dwy\FacebookConversion\listeners\cms\Search as CmsSearchEvent;
use dwy\FacebookConversion\listeners\commerce\cms\Search as CommerceSearchEvent;
use dwy\FacebookConversion\listeners\commerce\order\AfterAddLineItem;
use dwy\FacebookConversion\listeners\commerce\order\AfterCompleteOrder;
use dwy\FacebookConversion\hooks\HeadTag;
use dwy\FacebookConversion\listeners\molliepayments\AfterTransaction;
use dwy\FacebookConversion\models\Settings;
use dwy\FacebookConversion\services\FacebookBusinessApi;
use studioespresso\molliepayments\MolliePayments;
use studioespresso\molliepayments\services\Transaction;
use yii\base\Event;

class Plugin extends BasePlugin
{
    public $hasCpSettings = true;

    public function init()
    {
        Event::on(Application::class, Application::EVENT_INIT, function() {
            parent::init();

            ray()->showEvents();

            $this->_registerHooks();
            $this->_registerEventListeners();
            $this->_registerComponents();

            if (Craft::$app->getRequest()->getIsConsoleRequest()) {
                return;
            }

            $this->_getFacebookClickId();
        });
    }

    protected function createSettingsModel()
    {
        return new Settings();
    }

    protected function settingsHtml()
    {
        return Craft::$app->getView()->renderTemplate(
            'facebook-conversion/settings',
            [ 'settings' => $this->getSettings() ]
        );
    }

    private function _registerHooks()
    {
        Craft::$app->view->hook('facebook-conversion-head-tag', new HeadTag);
    }

    private function _registerEventListeners()
    {
        if (class_exists(Order::class)) {
            Event::on(Order::class, Order::EVENT_AFTER_ADD_LINE_ITEM, new AfterAddLineItem);
            Event::on(Order::class, Order::EVENT_AFTER_COMPLETE_ORDER, new AfterCompleteOrder);
            Event::on(Search::class, Search::EVENT_AFTER_SEARCH, new CommerceSearchEvent);
        }
        else {
            Event::on(Search::class, Search::EVENT_AFTER_SEARCH, new CmsSearchEvent);
        }

        if (class_exists(MolliePayments::class) && class_exists(Transaction::class)) {
            Event::on(Transaction::class, MolliePayments::EVENT_AFTER_TRANSACTION_UPDATE, new AfterTransaction);
        }
    }

    private function _registerComponents()
    {
        $this->setComponents([
            'facebook' => FacebookBusinessApi::class,
        ]);
    }

    private function _getFacebookClickId()
    {
        $fbclid = Craft::$app->getRequest()->get('fbclid');

        if ($fbclid) {
            Craft::$app->session->set('fbc', $fbclid);
        }
    }
}
