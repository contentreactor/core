<?php

namespace Developion\Core\Services;

use Craft;
use craft\base\Model;
use craft\base\PluginInterface;
use craft\events\PluginEvent;
use Developion\Core\Core;
use yii\base\Component;

class Plugins extends Component
{
	const EVENT_BEFORE_SAVE_PLUGIN_SETTINGS = 'beforeSavePluginSettings';

	const EVENT_AFTER_SAVE_PLUGIN_SETTINGS = 'afterSavePluginSettings';

	public function savePluginSettings(PluginInterface $plugin, array $settings): bool
	{
		$plugin->getSettings()->setAttributes($this->castRequestToModel($settings, $plugin), false);

		if ($plugin->getSettings()->validate() === false) {
			return false;
		}

		if ($this->hasEventHandlers(self::EVENT_BEFORE_SAVE_PLUGIN_SETTINGS)) {
			$this->trigger(self::EVENT_BEFORE_SAVE_PLUGIN_SETTINGS, new PluginEvent([
				'plugin' => $plugin,
			]));
		}

		if (!$plugin->beforeSaveSettings()) {
			return false;
		}

		if (!Core::getInstance()->db->setPluginSettings($plugin, $settings)) {
			return false;
		}

		$plugin->afterSaveSettings();

		if ($this->hasEventHandlers(self::EVENT_AFTER_SAVE_PLUGIN_SETTINGS)) {
			$this->trigger(self::EVENT_AFTER_SAVE_PLUGIN_SETTINGS, new PluginEvent([
				'plugin' => $plugin,
			]));
		}

		return true;
	}

	public function getPluginSettings(PluginInterface $plugin): Model
	{
		if (array_key_exists('plugin', Craft::$app->getUrlManager()->getRouteParams())) {
			/** @var PluginInterface $plugin */
			$plugin = Craft::$app->getUrlManager()->getRouteParams()['plugin'];
			$settings = $plugin->getSettings();
		} else {
			$settings = Core::getInstance()->db->getPluginSettings($plugin);
		}
		$plugin->getSettings()->setAttributes($settings, false);

		return $plugin->getSettings();
	}

	private function castRequestToModel(array $settings, PluginInterface $plugin): array
	{
		foreach ($settings as $key => &$value) {
			if (gettype($plugin->getSettings()->$key) == 'array' && empty($value)) {
				$value = [];
			}
			settype($value, gettype($plugin->getSettings()->$key));
		}

		return $settings;
	}
}
