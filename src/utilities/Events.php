<?php

namespace dwy\FacebookConversion\utilities;

use Craft;
use craft\base\Utility;
use craft\web\twig\variables\Paginate;
use dwy\FacebookConversion\Plugin;
use yii\web\ForbiddenHttpException;

class Events extends Utility
{
    public static function displayName(): string
    {
        return Craft::t('facebook-conversion', 'Server Events');
    }

    public static function id(): string
    {
        return 'facebook-conversion-events';
    }

    public static function icon(): ?string
    {
        return Craft::getAlias('@dwy/FacebookConversion/resources/utility-icon.svg');
    }

    public static function contentHtml(): string
    {
        if (!Craft::$app->getUser()->checkPermission(Plugin::PERMISSION_VIEW_EVENT_LOGS)) {
            throw new ForbiddenHttpException();
        }

        $plugin = Plugin::getInstance();
        $settings = $plugin->getSettings();

        $pageNum = Craft::$app->getRequest()->getPageNum();
        $paginator = $plugin->events->getEventsPaginatedQuery($pageNum);

        return Craft::$app->getView()->renderTemplate(
            'facebook-conversion/events/index',
            [
                'events' => $paginator->getPageResults(),
                'pageInfo' => Paginate::create($paginator),
                'settings' => $settings,
            ]
        );
    }
}
