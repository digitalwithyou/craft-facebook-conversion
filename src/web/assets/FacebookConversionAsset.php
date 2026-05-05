<?php

namespace dwy\FacebookConversion\web\assets;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

class FacebookConversionAsset extends AssetBundle
{
    public function init()
    {
        $this->sourcePath = "@dwy/FacebookConversion/resources";

        $this->depends = [
            CpAsset::class,
        ];

        $this->css = [
            'cp-facebook-conversion.css',
        ];

        $this->js = [
            'cp-facebook-conversion.js',
        ];

        parent::init();
    }
}
