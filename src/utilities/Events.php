<?php

namespace dwy\FacebookConversion\utilities;

use Craft;
use craft\base\Utility;
use dwy\FacebookConversion\Plugin;

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

    public static function iconPath(): ?string
    {
        return Craft::getAlias('@dwy/FacebookConversion/icon.svg');
    }

    public static function contentHtml(): string
    {
        $page = Craft::$app->getRequest()->getPageNum();
        $limit = 50;
        $offset = ($page - 1) * $limit;

        $events = Plugin::getInstance()->events->getEvents($limit, $offset);
        $totalEvents = Plugin::getInstance()->events->getTotalEvents();
        $totalPages = ceil($totalEvents / $limit);

        return Craft::$app->getView()->renderTemplate(
            'facebook-conversion/events/index',
            [
                'events' => $events,
                'currentPage' => $page,
                'totalPages' => $totalPages,
            ]
        );
    }
}
