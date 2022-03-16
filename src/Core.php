<?php

namespace Developion\Core;

use Craft;
use craft\base\Plugin;
use craft\events\PluginEvent;
use craft\events\RegisterTemplateRootsEvent;
use craft\i18n\PhpMessageSource;
use craft\services\Plugins;
use craft\web\View;
use Developion\Core\web\twig\Extension;
use yii\base\Event;

class Core extends Plugin
{
	public static Core $plugin;

	public $schemaVersion = '0.5.0';

	public function init(): void
	{
		parent::init();
		self::$plugin = $this;

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
			Plugins::class,
			Plugins::EVENT_BEFORE_UNINSTALL_PLUGIN,
			function (PluginEvent $event) {
				$developionPlugins = array_filter(array_keys(Craft::$app->getPlugins()->getAllPlugins()), function ($pluginHandle) {
					return $pluginHandle != 'developion-core' && str_contains($pluginHandle, 'developion');
				});
				if ($event->plugin === $this) {
					foreach ($developionPlugins as $developionPlugin) {
						Craft::$app->getPlugins()->uninstallPlugin($developionPlugin);
					}
				}
			}
		);
	}

	protected function _consoleEvents(): void
	{
	}

	protected function _twigExtensions(): void
	{
		Craft::$app->view->registerTwigExtension(new Extension);
	}
}
