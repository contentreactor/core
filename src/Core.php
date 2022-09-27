<?php

namespace ContentReactor\Core;

use Craft;
use craft\base\Plugin;
use craft\events\PluginEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterTemplateRootsEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\helpers\ArrayHelper;
use craft\helpers\UrlHelper;
use craft\i18n\PhpMessageSource;
use craft\services\Fields as FieldsService;
use craft\services\Plugins as CraftPlugins;
use craft\web\UrlManager;
use craft\web\View;
use ContentReactor\Core\fields\Button as ButtonField;
use ContentReactor\Core\fields\Link as LinkField;
use ContentReactor\Core\Models\Settings;
use ContentReactor\Core\Records\Setting;
use ContentReactor\Core\Services\DB;
use ContentReactor\Core\Services\Plugins;
use ContentReactor\Core\web\twig\Extension;
use ContentReactor\Core\web\twig\variables\ContentReactor as CRVariable;
use craft\web\twig\variables\CraftVariable;
use Illuminate\Support\Collection;
use yii\base\Event;

/**
 * Class Core
 *
 * @package contentreactor/core
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
		Craft::setAlias('@core', __DIR__);

		$request = Craft::$app->getRequest();
		if ($request->getIsConsoleRequest()) {
			$this->_consoleEvents();
		}

		$this->_config();
		$this->_events();
		$this->_twigExtensions();
		$this->_environment();
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
		// dd(Craft::$app->getI18n());
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
				$event->rules["{$this->id}/settings/save"] = "{$this->id}/settings/save";
				$event->rules["{$this->id}/settings"] = "{$this->id}/settings";
			}
		);

		Event::on(
			UrlManager::class,
			UrlManager::EVENT_REGISTER_SITE_URL_RULES,
			function (RegisterUrlRulesEvent $event) {
				$event->rules['POST cache/clear'] = "{$this->id}/cache";
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
				$path = Craft::$app->getPath()->getStoragePath() . "/{$event->plugin->getHandle()}-site-{$currentSite->id}.php";
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

		Event::on(
			FieldsService::class,
			FieldsService::EVENT_REGISTER_FIELD_TYPES,
			static function (RegisterComponentTypesEvent $event) {
				$event->types[] = ButtonField::class;
				$event->types[] = LinkField::class;
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

	private function _environment(): void
	{
		if (version_compare(Craft::$app->getVersion(), '4.1.0', '<')) {
			Collection::macro('one', function () {
				return $this->first(...func_get_args());
			});
		}

		Collection::macro('filterMap', function (bool $condition, ?callable $callback) {
			$return = [];
			foreach ($this as $key => $value) {
				if (!$condition) continue;
				$return[$key] = $value;
			}
			return $return;
		});
	}
}
