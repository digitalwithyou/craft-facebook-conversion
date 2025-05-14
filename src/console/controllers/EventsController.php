<?php

namespace dwy\FacebookConversion\console\controllers;

use craft\console\Controller;
use craft\helpers\Console;
use dwy\FacebookConversion\Plugin;
use yii\console\ExitCode;

class EventsController extends Controller
{
    public function actionCleanup()
    {
        $this->note("Cleaning up Facebook Conversion events...");

        Plugin::getInstance()->events->cleanup();

        $this->success("Done!");

        return ExitCode::OK;
    }
}
