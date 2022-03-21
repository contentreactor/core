<?php

namespace Developion\Core\Services;

use Craft;
use craft\base\PluginInterface;
use craft\helpers\ArrayHelper;
use Developion\Core\Records\Setting;

class DB
{
	public function savePluginSettings(PluginInterface $plugin, array $settings): bool
	{
		$currentSite = Craft::$app->getSites()->getCurrentSite();
		$transaction = Craft::$app->getDb()->beginTransaction();
		try {
			foreach ($settings as $settingKey => $settingValue) {
				$setting = Setting::findOne([
					'plugin' => $plugin->id,
					'siteId' => $currentSite->id,
					'key' => $plugin->id . '_' . $settingKey
				]);
				if (!$setting) {
					$setting = new Setting([
						'plugin' => $plugin->id,
						'siteId' => $currentSite->id,
						'key' => $plugin->id . '_' . $settingKey
					]);
				}
				if (gettype($plugin->getSettings()->$settingKey) == 'array' && empty($settingValue)) {
					$settingValue = [];
				}
				settype($settingValue, gettype($plugin->getSettings()->$settingKey));
				$setting->value = serialize($settingValue);
				$setting->save();
			}
			$transaction->commit();
		} catch (\Throwable $th) {
			$transaction->rollBack();
			return false;
		}
		return true;
	}

	public function getPluginSettings(PluginInterface $plugin): array
	{
		$currentSite = Craft::$app->getSites()->getCurrentSite();
		$settings = ArrayHelper::map(
			Setting::find()
				->select(['key', 'value'])
				->where([
					'plugin' => $plugin->id,
					'siteId' => $currentSite->id,
				])
				->all(),
			fn (Setting $setting) => substr($setting->key, strlen($plugin->id . '_')),
			fn (Setting $setting) => unserialize($setting->value)
		);
		return $settings;
	}

	public function getPluginSetting(PluginInterface $plugin, string $key): mixed
	{
		$currentSite = Craft::$app->getSites()->getCurrentSite();
		$setting = Setting::find()
			->select(['key', 'value'])
			->where([
				'plugin' => $plugin->id,
				'siteId' => $currentSite->id,
				'key' => $plugin->id . '_' . $key,
			])
			->one();
		return unserialize($setting->value);
	}
}
