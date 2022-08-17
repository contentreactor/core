<?php

namespace Contentreactor\Core\traits;

use Craft;
use craft\elements\Entry;
use craft\events\PluginEvent;
use craft\services\Plugins;
use Contentreactor\Core\Core;
use Contentreactor\Core\events\ContentreactorPluginEvent;
use Contentreactor\Core\Records\Setting;
use ReflectionClass;
use yii\base\Event;

trait IsContentreactorPlugin
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
					Craft::$app->getPlugins()->installPlugin('contentreactor-core');
					$core = Core::getInstance();
					$contentreactorPlugins = $core->db->getPluginSetting($core, 'contentreactorPlugins');
					if (null !== $this->getSettings()) {
						$contentreactorPlugins[] = $this->id;
						$core->plugins->savePluginSettings($this, $this->getSettings()->getAttributes());
					}
					$core->db->setPluginSetting($core, 'contentreactorPlugins', array_unique($contentreactorPlugins));
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
					$contentreactorPlugins = $core->db->getPluginSetting($core, 'contentreactorPlugins');
					if (null !== $this->getSettings()) unset($contentreactorPlugins[$this->id]);
					$core->db->setPluginSetting($core, 'contentreactorPlugins', $contentreactorPlugins);
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
		$event = new ContentreactorPluginEvent([
			'callbacks' => [],
		]);
		Event::trigger(
			ContentreactorPluginEvent::class,
			ContentreactorPluginEvent::EVENT_AT_PLUGIN_INIT,
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
		return false;
	}

	public function twigExtensions(): void
	{
	}
}
