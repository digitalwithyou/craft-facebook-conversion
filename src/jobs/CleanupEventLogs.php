<?php

namespace dwy\FacebookConversion\jobs;

use craft\queue\BaseJob;
use dwy\FacebookConversion\Plugin;

class CleanupEventLogs extends BaseJob
{
    public function execute($queue)
    {
        Plugin::getInstance()->events->cleanup();
    }

    protected function defaultDescription(): string
    {
        return 'Cleanup Facebook Conversion event logs';
    }
}
