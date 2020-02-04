<?php

namespace aelvan\morefitimages;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

class MoreFitImagesAssetBundle extends AssetBundle
{
    public function init()
    {
        // define the path that your publishable resources live
        $this->sourcePath = '@aelvan/more-fit-images/resources';

        // define the dependencies
        $this->depends = [
            CpAsset::class,
        ];

        // define the relative path to CSS/JS files that should be registered with the page
        // when this asset bundle is registered
        $this->js = [
            'more-fit-images.js',
        ];

        $this->css = [
            'more-fit-images.css',
        ];

        parent::init();
    }
}
