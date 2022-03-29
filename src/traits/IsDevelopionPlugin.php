<?php

namespace Developion\Core\traits;

use Craft;
use craft\elements\Entry;
use craft\events\PluginEvent;
use craft\services\Plugins;
use Developion\Core\Core;
use Developion\Core\events\DevelopionPluginEvent;
use Developion\Core\Records\Setting;
use ReflectionClass;
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
		$this->twigExtensions();
	}

	private function _dependencyEvents()
	{
		Event::on(
			Plugins::class,
			Plugins::EVENT_BEFORE_INSTALL_PLUGIN,
			function (PluginEvent $event) {
				if ($event->plugin === $this) {
					Craft::$app->getPlugins()->installPlugin('developion-core');
					$core = Core::getInstance();
					$developionPlugins = $core->db->getPluginSetting($core, 'developionPlugins');
					if (null !== $this->getSettings()) {
						$developionPlugins[] = $this->id;
						$core->plugins->savePluginSettings($this, $this->getSettings()->getAttributes());
					}
					$core->db->setPluginSetting($core, 'developionPlugins', $developionPlugins);
				}
			}
		);

		Event::on(
			Plugins::class,
			Plugins::EVENT_BEFORE_UNINSTALL_PLUGIN,
			function (PluginEvent $event) {
				if ($event->plugin === $this) {
					Setting::deleteAll([
						'plugin' => $this->id,
					]);
					$core = Core::getInstance();
					$developionPlugins = $core->db->getPluginSetting($core, 'developionPlugins');
					if (null !== $this->getSettings()) unset($developionPlugins[$this->id]);
					$core->db->setPluginSetting($core, 'developionPlugins', $developionPlugins);
				}
			}
		);

		Event::on(
			Plugins::class,
			Plugins::EVENT_AFTER_LOAD_PLUGINS,
			function () {
				$this->_trigger();
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

	abstract protected function _events(): void;

	public function getRouteByEntryType(Entry $entry)
	{
		$class_name = get_class($this);
		$reflection_class = new ReflectionClass($class_name);
		$namespace = $reflection_class->getNamespaceName();
		if (class_exists($namespace . "\controllers\\{$entry->type->name}Controller")) {
			return "{$this->id}/{$entry->type->handle}";
		}
		if (class_exists($namespace . "\controllers\\PageController")) {
			return "{$this->id}/page";
		}
		return false;
	}

	public function twigExtensions(): void
	{
	}
}
