<?php

namespace dwy\FacebookConversion;

use Craft;
use craft\base\Model;
use craft\base\Plugin as BasePlugin;
use craft\commerce\elements\Order;
use craft\services\Search;
use craft\web\Application;
use dwy\FacebookConversion\hooks\BodyTag;
use dwy\FacebookConversion\hooks\HeadTag;
use dwy\FacebookConversion\listeners\cms\Search as CmsSearchEvent;
use dwy\FacebookConversion\listeners\commerce\cms\Search as CommerceSearchEvent;
use dwy\FacebookConversion\listeners\commerce\order\AfterAddLineItem;
use dwy\FacebookConversion\listeners\commerce\order\AfterCompleteOrder;
use dwy\FacebookConversion\listeners\molliepayments\AfterTransaction;
use dwy\FacebookConversion\models\Settings;
use dwy\FacebookConversion\services\FacebookBusinessApi;
use dwy\FacebookConversion\twig\TwigExtension;
use studioespresso\molliepayments\MolliePayments;
use studioespresso\molliepayments\services\Transaction;
use yii\base\Event;

class Plugin extends BasePlugin
{
    public function init()
    {
        $this->hasCpSettings = true;

        Event::on(Application::class, Application::EVENT_INIT, function() {
            parent::init();

            $this->_registerHooks();
            $this->_registerEventListeners();
            $this->_registerComponents();

            if (Craft::$app->getRequest()->getIsConsoleRequest()) {
                return;
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
        ]);
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
}
