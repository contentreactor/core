<?php

namespace Developion\Core\Services;

use Craft;
use craft\base\PluginInterface;
use craft\helpers\ArrayHelper;
use Developion\Core\Records\Setting;

class DB
{
	/**
	 * Saves a plugin's settings.
	 *
	 * @param PluginInterface $plugin The plugin
	 * @param array $settings The plugin’s new settings
	 * @return bool Whether the plugin’s settings were saved successfully
	 */
	public function savePluginSettings(PluginInterface $plugin, array $settings): bool
	{
		$currentSite = Craft::$app->getSites()->getCurrentSite();
		$transaction = Craft::$app->getDb()->beginTransaction();
		try {
			foreach ($settings as $settingKey => $settingValue) {
				$setting = Setting::findOne([
					'plugin' => $plugin->id,
					'siteId' => $currentSite->id,
					'key' => $settingKey
				]);
				if (!$setting) {
					$setting = new Setting([
						'plugin' => $plugin->id,
						'siteId' => $currentSite->id,
						'key' => $settingKey
					]);
				}
				$setting->value = $settingValue;
				$setting->save();
			}
			$transaction->commit();
		} catch (\Throwable $th) {
			$transaction->rollBack();
			dd($th->getMessage());
			return false;
		}
		return true;
	}

	/**
	 * Retrieves a plugin's settings.
	 *
	 * @param PluginInterface $plugin The plugin
	 * @return array An array of retrieved settings. An empty array if none found
	 */
	public function getPluginSettings(PluginInterface $plugin)
	{
		$currentSite = Craft::$app->getSites()->getCurrentSite();
		$settings = ArrayHelper::map(
			Setting::findAll([
				'plugin' => $plugin->id,
				'siteId' => $currentSite->id,
			]),
			fn(Setting $setting) => $setting->key,
			fn(Setting $setting) => $setting->value
		);
		return $settings;
	}
}
