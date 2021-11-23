<?php

namespace Developion\Core\traits;

use Craft;
use craft\events\PluginEvent;
use craft\services\Plugins;
use Developion\Core\events\DevelopionPluginEvent;
use yii\base\Event;

trait IsDevelopionPlugin
{
	public static $plugin;

	public function init()
	{
		parent::init();
		self::$plugin = $this;

		$this->_dependencyEvents();
		$this->_events();

		$this->_trigger();
	}

	private function _dependencyEvents()
	{
		Event::on(
			Plugins::class,
			Plugins::EVENT_BEFORE_INSTALL_PLUGIN,
			function (PluginEvent $event) {
				if ($event->plugin === $this) {
					Craft::$app->getPlugins()->installPlugin('developion-core');
				}
			}
		);
	}

	private function _trigger()
	{
		$event = new DevelopionPluginEvent([
			'callbacks' => [],
		]);
		Event::trigger(
			DevelopionPluginEvent::class,
			DevelopionPluginEvent::EVENT_AT_PLUGIN_INIT,
			$event
		);
		
		foreach ($event->callbacks as $callback) {
			if (is_callable($callback)) $callback();
		}
	}

	abstract protected function _events();
}