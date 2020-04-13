<?php
/**
 * Asset Purge plugin for Craft CMS 3.x
 *
 * Provides an interface to view unused assets.
 *
 * @link      https://github.com/fvaldes33
 * @copyright Copyright (c) 2020 fvaldes33
 */

namespace fvaldes33\assetpurge\assetbundles\AssetPurge;

use Craft;
use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

/**
 * @author    fvaldes33
 * @package   AssetPurge
 * @since     1.0.0
 */
class AssetPurgeAsset extends AssetBundle
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->sourcePath = "@fvaldes33/assetpurge/assetbundles/assetpurge/dist";

        $this->depends = [
            CpAsset::class,
        ];

        $this->js = [
            'js/AssetPurge.js',
        ];

        $this->css = [
            'css/AssetPurge.css',
        ];

        parent::init();
    }
}
