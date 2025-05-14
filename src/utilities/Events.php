<?php

namespace dwy\FacebookConversion\utilities;

use Craft;
use craft\base\Utility;
use craft\web\twig\variables\Paginate;
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
        $pageNum = Craft::$app->getRequest()->getPageNum();

        $paginator = Plugin::getInstance()->events->getEventsPaginatedQuery($pageNum);

        return Craft::$app->getView()->renderTemplate(
            'facebook-conversion/events/index',
            [
                'events' => $paginator->getPageResults(),
                'pageInfo' => Paginate::create($paginator),
            ]
        );
    }
}
