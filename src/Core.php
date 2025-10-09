<?php
declare(strict_types=1);

namespace ContentReactor\Core;

use ContentReactor\Core\Models\Settings;
use ContentReactor\Core\Records\Setting;
use ContentReactor\Core\Services\{
	DB,
	Plugins,
};
use ContentReactor\Core\Web\Twig\Extension;
use ContentReactor\Core\Web\Twig\Variables\ContentReactor as CRVariable;
use Craft;
use craft\events\{
	PluginEvent,
	RegisterTemplateRootsEvent,
	RegisterUrlRulesEvent,
};
use craft\helpers\{
	ArrayHelper,
	UrlHelper,
};
use craft\i18n\PhpMessageSource;
use craft\services\Plugins as CraftPlugins;
use craft\web\{
	Response,
	UrlManager,
	View,
};
use craft\web\twig\variables\CraftVariable;
use yii\base\Event;
use yii\base\Module;

/**
 * Class Core
 *
 * @package contentreactor/core
 *
 * @property Core $plugin
 * @property Plugins $plugins
 * @property DB $db
 */
class Core extends Module
{
	public const ID = 'Core';

	public function init(): void
	{
		parent::init();
		$this->id = self::ID;
		Craft::setAlias('@core', __DIR__);

		$request = Craft::$app->getRequest();
		if ($request->getIsConsoleRequest()) {
			$this->_consoleEvents();
		}

		$this->_config();
	}

	protected function _config(): void
	{
		Craft::$app->getI18n()->translations['site'] = [
			'class' => PhpMessageSource::class,
			'sourceLanguage' => 'en',
			'basePath' => '@core/translations',
			'forceTranslation' => true,
			'allowOverrides' => true,
		];
		$this->setComponents([
			'db' => DB::class,
			'plugins' => Plugins::class,
		]);

		Event::on(
			CraftVariable::class,
			CraftVariable::EVENT_INIT,
			function (Event $event) {
				$variable = $event->sender;
				$variable->set('cr', CRVariable::class);
			}
		);
	}

	public function getSettingsResponse(): Response
	{
		return Craft::$app->getResponse()->redirect(UrlHelper::cpUrl("$this->id/settings"));
	}

	protected function _events(): void
	{
		Event::on(
			View::class,
			View::EVENT_REGISTER_SITE_TEMPLATE_ROOTS,
			function (RegisterTemplateRootsEvent $event) {
				$event->roots['contentreactor-core'] = __DIR__ . '/templates';
			}
		);

		Event::on(
			UrlManager::class,
			UrlManager::EVENT_REGISTER_CP_URL_RULES,
			function (RegisterUrlRulesEvent $event) {
				$event->rules["$this->id/settings/save"] = "$this->id/settings/save";
				$event->rules["$this->id/settings"] = "$this->id/settings";
			}
		);

		Event::on(
			UrlManager::class,
			UrlManager::EVENT_REGISTER_SITE_URL_RULES,
			function (RegisterUrlRulesEvent $event) {
				$event->rules['POST cache/clear'] = "$this->id/cache";
			}
		);

		Event::on(
			CraftPlugins::class,
			CraftPlugins::EVENT_BEFORE_UNINSTALL_PLUGIN,
			function (PluginEvent $event) {
				if ($event->plugin === $this) {
					$contentreactorPlugins = array_filter(array_keys(Craft::$app->getPlugins()->getAllPlugins()), function ($pluginHandle) {
						return $pluginHandle != 'contentreactor-core' && str_contains($pluginHandle, 'contentreactor');
					});
					foreach ($contentreactorPlugins as $contentreactorPlugin) {
						Craft::$app->getPlugins()->uninstallPlugin($contentreactorPlugin);
					}
				}
			}
		);

		Event::on(
			Plugins::class,
			Plugins::EVENT_BEFORE_SAVE_PLUGIN_SETTINGS,
			function (PluginEvent $event) {
				if (stripos($event->plugin->getHandle(), 'contentreactor') === false) return;
				$currentSite = Craft::$app->getSites()->getCurrentSite();
				$path = sprintf(
					'%s/%s-site-%s.php',
					Craft::$app->getPath()->getStoragePath(),
					$event->plugin->getHandle(),
					$currentSite->id,
				);
				if (!file_exists($path)) {
					file_put_contents($path, "<?php\n\nreturn [];\n");
				}
			}
		);

		Event::on(
			Plugins::class,
			Plugins::EVENT_AFTER_SAVE_PLUGIN_SETTINGS,
			function (PluginEvent $event) {
				if (stripos($event->plugin->getHandle(), 'contentreactor') === false) return;
				$currentSite = Craft::$app->getSites()->getCurrentSite();
				$prefix = $event->plugin->id . '_';
				$path = sprintf(
					'%s/%s-site-%s.php',
					Craft::$app->getPath()->getStoragePath(),
					$event->plugin->getHandle(),
					$currentSite->id,
				);
				$settings = $this->db->getPluginSettingsRaw($event->plugin);
				$settings = ArrayHelper::map(
					$settings,
					fn(Setting $setting) => substr($setting->key, strlen($prefix)),
					fn(Setting $setting) => unserialize($setting->value)
				);
				file_put_contents($path, "<?php\n\nreturn " . var_export($settings, true) . ";\n");
			}
		);
	}

	protected function createSettingsModel(): Settings
	{
		return new Settings();
	}
}
