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
	/**
	 * @event PluginEvent The event that is triggered before a plugin's settings are saved
	 */
	const EVENT_BEFORE_SAVE_PLUGIN_SETTINGS = 'beforeSavePluginSettings';

	/**
	 * @event PluginEvent The event that is triggered after a plugin's settings are saved
	 */
	const EVENT_AFTER_SAVE_PLUGIN_SETTINGS = 'afterSavePluginSettings';

	/**
	 * Saves a plugin's settings.
	 *
	 * @param PluginInterface $plugin The plugin
	 * @param array $settings The plugin’s new settings
	 * @return bool Whether the plugin’s settings were saved successfully
	 */
	public function savePluginSettings(PluginInterface $plugin, array $settings): bool
	{
		$plugin->getSettings()->setAttributes($settings, false);

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

		if (!Core::getInstance()->db->savePluginSettings($plugin, $settings)) {
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
			$plugin = Craft::$app->getUrlManager()->getRouteParams()['plugin'];
			$settings = $plugin->getSettings();
		} else {
			$settings = Core::getInstance()->db->getPluginSettings($plugin);
		}
		$plugin->getSettings()->setAttributes($settings, false);
		
		return $plugin->getSettings();
	}
}
