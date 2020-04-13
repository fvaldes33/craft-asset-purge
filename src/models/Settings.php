<?php
/**
 * Asset Purge plugin for Craft CMS 3.x
 *
 * Provides an interface to view unused assets.
 *
 * @link      https://github.com/fvaldes33
 * @copyright Copyright (c) 2020 fvaldes33
 */

namespace fvaldes33\assetpurge\models;

use fvaldes33\assetpurge\AssetPurge;

use Craft;
use craft\base\Model;

/**
 * @author    fvaldes33
 * @package   AssetPurge
 * @since     1.0.0
 */
class Settings extends Model
{
    // Public Properties
    // =========================================================================

    /**
     * @var boolean
     */
    public $autoPurge = false;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['autoPurge', 'boolean'],
            ['autoPurge', 'default', 'value' => false],
        ];
    }
}
