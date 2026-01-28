<?php

namespace dwy\FacebookConversion\hooks;

use craft\commerce\Plugin as Commerce;
use dwy\FacebookConversion\Plugin;

class HeadTag
{
    public function __invoke(array &$context): string
    {
        $plugin = Plugin::getInstance();
        $settings = $plugin->getSettings();

        $pixelId = $settings->getPixelId();
        $options = '';

        if (empty($pixelId)) {
            return '';
        }

        if (class_exists(Commerce::class)) {
            $email = Commerce::getInstance()
                ->getCarts()
                ->getCart()
                ->getEmail();

            if ($email) {
                $externalId = $plugin->getExternalId($email);
                $options = ", {'external_id': '{$externalId}'}";
            }
        }

        return <<<EOD
<!-- Facebook Pixel Code -->
<script>
    !function(f,b,e,v,n,t,s)
    {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
    n.callMethod.apply(n,arguments):n.queue.push(arguments)};
    if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
    n.queue=[];t=b.createElement(e);t.async=!0;
    t.src=v;s=b.getElementsByTagName(e)[0];
    s.parentNode.insertBefore(t,s)}(window, document,'script',
    'https://connect.facebook.net/en_US/fbevents.js');
    fbq('init', '$pixelId'$options);
    fbq('track', 'PageView');
</script>
<!-- End Facebook Pixel Code -->
EOD;
    }
}
