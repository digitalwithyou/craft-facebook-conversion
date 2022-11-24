<?php

namespace dwy\FacebookConversion\hooks;

use dwy\FacebookConversion\Plugin;

class BodyTag
{
    public function __invoke(array &$context): string
    {
        $plugin = Plugin::getInstance();
        $settings = $plugin->getSettings();

        $pixelId = $settings->getPixelId();

        if (empty($pixelId)) {
            return '';
        }

        return <<<EOD
<!-- Facebook Pixel Code -->
<noscript><img height="1" width="1" style="display:none"
    src="https://www.facebook.com/tr?id=$pixelId&ev=PageView&noscript=1"
/></noscript>
<!-- End Facebook Pixel Code -->
EOD;
    }
}
