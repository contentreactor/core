<?php

namespace Developion\Core;

use Craft;
use craft\base\Plugin;
use craft\events\PluginEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterTemplateRootsEvent;
use craft\i18n\PhpMessageSource;
use craft\services\Fields;
use craft\services\Plugins;
use craft\web\View;
use Developion\Core\fields\InceptionMatrix;
use Developion\Core\services\ImagesService;
use Developion\Core\services\InstallService;
use Developion\Core\web\twig\Extension;
use yii\base\Event;

class Core extends Plugin
{
	public static $plugin;

	public $schemaVersion = '0.2.4';

	public function init()
	{
		parent::init();
		self::$plugin = $this;

		$this->setComponents([
			'images' => ImagesService::class,
			'install' => InstallService::class,
		]);

		$request = Craft::$app->getRequest();
		if ($request->getIsConsoleRequest()) {
			$this->_consoleEvents();
		}
		
		$this->_config();
		$this->_events();
		$this->_twigExtensions();
	}

	protected function _config()
	{
		Craft::$app->getI18n()->translations['core'] = [
			'class' => PhpMessageSource::class,
			'basePath' => __DIR__ . '/translations',
			'allowOverrides' => true,
			'forceTranslation' => true,
		];
	}

	protected function _events()
	{
		Event::on(
			View::class,
			View::EVENT_REGISTER_SITE_TEMPLATE_ROOTS,
			function (RegisterTemplateRootsEvent $event) {
				$event->roots['developion-core'] = __DIR__ . '/templates';
			}
		);

		Event::on(
			Fields::class,
			Fields::EVENT_REGISTER_FIELD_TYPES,
			function (RegisterComponentTypesEvent $event) {
				$event->types[] = InceptionMatrix::class;
			}
		);

		Event::on(
			Plugins::class,
			Plugins::EVENT_AFTER_INSTALL_PLUGIN,
			function (PluginEvent $event) {
				if ($event->plugin === $this) {
					// $this->_registerSettings();
				}
			}
		);

		Event::on(
			Plugins::class,
			Plugins::EVENT_BEFORE_UNINSTALL_PLUGIN,
			function (PluginEvent $event) {
				$developionPlugins = array_filter(array_keys(Craft::$app->getPlugins()->getAllPlugins()), function($pluginHandle) {
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

	protected function _consoleEvents()
	{
	}

	protected function _twigExtensions()
	{
		Craft::$app->view->registerTwigExtension(new Extension);
	}
}
