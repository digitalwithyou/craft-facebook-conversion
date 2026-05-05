<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

// Yii's validators call `Yii::createObject(...)` during validation; load the
// global `Yii` class so model validation works in unit tests without Craft.
if (!class_exists('Yii', false)) {
    require __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';
}
