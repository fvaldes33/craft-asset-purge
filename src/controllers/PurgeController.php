<?php
/**
 * Asset Purge plugin for Craft CMS 3.x
 *
 * Provides an interface to view unused assets.
 *
 * @link      https://github.com/fvaldes33
 * @copyright Copyright (c) 2020 fvaldes33
 */

namespace fvaldes33\assetpurge\controllers;

use fvaldes33\assetpurge\AssetPurge;

use Craft;
use craft\web\Controller;

/**
 * @author    fvaldes33
 * @package   AssetPurge
 * @since     1.0.0
 */
class PurgeController extends Controller
{

    // Protected Properties
    // =========================================================================

    /**
     * @var    bool|array Allows anonymous access to this controller's actions.
     *         The actions must be in 'kebab-case'
     * @access protected
     */
    protected $allowAnonymous = ['index', 'do-something'];

    // Public Methods
    // =========================================================================

    /**
     * @return mixed
     */
    public function actionIndex()
    {
        $result = 'Welcome to the PurgeController actionIndex() method';

        return $result;
    }

    /**
     * @return mixed
     */
    public function actionDoSomething()
    {
        $result = 'Welcome to the PurgeController actionDoSomething() method';

        return $result;
    }
}
