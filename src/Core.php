<?php

namespace Developion\Core;

use Craft;
use craft\base\Plugin;
use craft\events\PluginEvent;
use craft\events\RegisterTemplateRootsEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\helpers\ArrayHelper;
use craft\helpers\UrlHelper;
use craft\i18n\PhpMessageSource;
use craft\services\Plugins as CraftPlugins;
use craft\web\UrlManager;
use craft\web\View;
use Developion\Core\Models\Settings;
use Developion\Core\Records\Setting;
use Developion\Core\Services\DB;
use Developion\Core\Services\Plugins;
use Developion\Core\web\twig\Extension;
use yii\base\Event;

/**
 * Class Core
 *
 * @package developion/core
 *
 * @property Core $plugin
 * @property Plugins $plugins
 * @property DB $db
 */
class Core extends Plugin
{
	public static Core $plugin;

	public bool $hasCpSettings = true;

	public string $schemaVersion = '1.0.0';

	public function init(): void
	{
		parent::init();
		self::$plugin = $this;
		$this->name = 'Core';

		$request = Craft::$app->getRequest();
		if ($request->getIsConsoleRequest()) {
			$this->_consoleEvents();
		}

		$this->_config();
		$this->_events();
		$this->_twigExtensions();
	}

	protected function _config(): void
	{
		Craft::$app->getI18n()->translations['core'] = [
			'class' => PhpMessageSource::class,
			'basePath' => __DIR__ . '/translations',
			'allowOverrides' => true,
			'forceTranslation' => true,
		];
		$this->setComponents([
			'db' => DB::class,
			'plugins' => Plugins::class,
		]);
	}

	protected function _events(): void
	{
		Event::on(
			View::class,
			View::EVENT_REGISTER_SITE_TEMPLATE_ROOTS,
			function (RegisterTemplateRootsEvent $event) {
				$event->roots['developion-core'] = __DIR__ . '/templates';
			}
		);
		Event::on(
			UrlManager::class,
			UrlManager::EVENT_REGISTER_CP_URL_RULES,
			function (RegisterUrlRulesEvent $event) {
				$event->rules["{$this->id}/settings/save"] = "{$this->id}/settings/save";
				$event->rules["{$this->id}/settings"] = "{$this->id}/settings";
			}
		);
		Event::on(
			CraftPlugins::class,
			CraftPlugins::EVENT_BEFORE_UNINSTALL_PLUGIN,
			function (PluginEvent $event) {
				if ($event->plugin === $this) {
					$developionPlugins = array_filter(array_keys(Craft::$app->getPlugins()->getAllPlugins()), function ($pluginHandle) {
						return $pluginHandle != 'developion-core' && str_contains($pluginHandle, 'developion');
					});
					foreach ($developionPlugins as $developionPlugin) {
						Craft::$app->getPlugins()->uninstallPlugin($developionPlugin);
					}
				}
			}
		);
		Event::on(
			Plugins::class,
			Plugins::EVENT_BEFORE_SAVE_PLUGIN_SETTINGS,
			function(PluginEvent $event) {
				if (stripos($event->plugin->getHandle(), 'developion') === false) return;
				$currentSite = Craft::$app->getSites()->getCurrentSite();
				$path = Craft::$app->getPath()->getStoragePath() . "/{$event->plugin->getHandle()}-site-{$currentSite->id}.php";
				if (!file_exists($path)) {
					file_put_contents($path, "<?php\n\nreturn [];\n");
				}
			}
		);
		Event::on(
			Plugins::class,
			Plugins::EVENT_AFTER_SAVE_PLUGIN_SETTINGS,
			function(PluginEvent $event) {
				if (stripos($event->plugin->getHandle(), 'developion') === false) return;
				$currentSite = Craft::$app->getSites()->getCurrentSite();
				$prefix = $event->plugin->id . '_';
				$path = Craft::$app->getPath()->getStoragePath() . "/{$event->plugin->getHandle()}-site-{$currentSite->id}.php";
				$settings = $this->db->getPluginSettingsRaw($event->plugin);
				$settings = ArrayHelper::map(
					$settings,
					fn (Setting $setting) => substr($setting->key, strlen($prefix)),
					fn (Setting $setting) => unserialize($setting->value)
				);
				file_put_contents($path, "<?php\n\nreturn " . var_export($settings, true) . ";\n");
			}
		);
	}

	protected function _consoleEvents(): void
	{
	}

	protected function _twigExtensions(): void
	{
		Craft::$app->getView()->registerTwigExtension(new Extension);
	}

	protected function createSettingsModel(): Settings
	{
		return new Settings();
	}

    public function getSettingsResponse(): mixed
    {
        return Craft::$app->getResponse()->redirect(UrlHelper::cpUrl("{$this->id}/settings"));
    }
}
