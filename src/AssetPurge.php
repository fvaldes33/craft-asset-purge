<?php
/**
 * Asset Purge plugin for Craft CMS 3.x
 *
 * Provides an interface to view unused assets.
 *
 * @link      https://github.com/fvaldes33
 * @copyright Copyright (c) 2020 fvaldes33
 */

namespace fvaldes33\assetpurge;

use fvaldes33\assetpurge\services\Purge as PurgeService;
use craft\elements\db\AssetQuery;

use craft\events\CancelableEvent;

use craft\elements\db\ElementQuery;

use fvaldes33\assetpurge\variables\AssetPurgeVariable;
use fvaldes33\assetpurge\models\Settings;

use Craft;
use craft\base\Plugin;
use craft\services\Plugins;
use craft\events\PluginEvent;
use craft\web\UrlManager;
use craft\web\twig\variables\CraftVariable;
use craft\events\RegisterUrlRulesEvent;

use yii\base\Event;

/**
 * Class AssetPurge
 *
 * @author    fvaldes33
 * @package   AssetPurge
 * @since     1.0.0
 *
 * @property  PurgeService $purge
 */
class AssetPurge extends Plugin
{
    // Static Properties
    // =========================================================================

    /**
     * @var AssetPurge
     */
    public static $plugin;

    // Public Properties
    // =========================================================================

    /**
     * @var string
     */
    public $schemaVersion = '1.0.0';

    /**
     * @var bool
     */
    public $hasCpSettings = true;

    /**
     * @var bool
     */
    public $hasCpSection = true;

    /**
     * @var string|null The plugin developer’s name
     */
    public $developer = "Franco Valdes";

    /**
     * @var string|null The plugin developer’s website URL
     */
    public $developerUrl = "https://github.com/fvaldes33";

    /**
     * @var string|null The plugin developer’s support email
     */
    public $developerEmail = "franco@appvents.com";

    /**
     * @var string|null The plugin’s documentation URL
     */
    public $documentationUrl = "https://github.com/fvaldes33/craft-asset-purge/blob/master/README.md";

    /**
     * @var string|null The plugin’s changelog URL.
     */
    public $changelogUrl = "https://raw.githubusercontent.com/fvaldes33/craft-asset-purge/master/CHANGELOG.md";

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        self::$plugin = $this;

        $this->_registerComponents();

        Event::on(
            ElementQuery::class,
            ElementQuery::EVENT_BEFORE_PREPARE,
            function(CancelableEvent $event) {
                $query = $event->sender;
                if ($query instanceof AssetQuery && isset($query->kind) && $query->kind === 'purge') {
                    $this->purge->alterAssetQuery($query);
                }
            }
        );

        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                $event->rules['purge'] = ["template" => "asset-purge/index"];
            }
        );

        Event::on(
            CraftVariable::class,
            CraftVariable::EVENT_INIT,
            function (Event $event) {
                /** @var CraftVariable $variable */
                $variable = $event->sender;
                $variable->set('assetPurge', AssetPurgeVariable::class);
            }
        );

        Event::on(
            Plugins::class,
            Plugins::EVENT_AFTER_INSTALL_PLUGIN,
            function (PluginEvent $event) {
                if ($event->plugin === $this) {

                }
            }
        );

        Craft::info(
            Craft::t(
                'asset-purge',
                '{name} plugin loaded',
                ['name' => $this->name]
            ),
            __METHOD__
        );
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function createSettingsModel()
    {
        return new Settings();
    }

    /**
     * @inheritdoc
     */
    protected function settingsHtml(): string
    {
        return Craft::$app->view->renderTemplate(
            'asset-purge/settings',
            [
                'settings' => $this->getSettings()
            ]
        );
    }

    // Private Methods
    // =========================================================================

    /**
     * Register all service level components
     *
     * @return void
     */
    private function _registerComponents(): void
    {
        $this->setComponents([
            "purge" => PurgeService::class
        ]);
    }
}
