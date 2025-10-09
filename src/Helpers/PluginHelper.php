<?php
declare(strict_types=1);

namespace ContentReactor\Core\Helpers;

use ContentReactor\Core\Base\ConfiglessSettingsContract;
use ContentReactor\Core\Records\Setting;
use Craft;
use craft\base\Model;
use craft\base\Plugin;

class PluginHelper
{
	public static function saveSettings(Plugin $plugin, ConfiglessSettingsContract&Model $settings): bool
	{
		$siteId = Craft::$app->getSites()->getCurrentSite()->id;
		$oldSettings = clone $settings;
		$settings->load(Craft::$app->getRequest()->getBodyParams());
		if (!$settings->validate()) return false;

		if ($settings->toArray() === $oldSettings->toArray()) {
			return true;
		}

		$setting = Setting::find()
			->where([
				'plugin' => $plugin->id,
				'key' => $settings::class,
				'siteId' => $siteId,
			])
			->one();
		if (!$setting) {
			$setting = new Setting();
			$setting->siteId = $siteId;
			$setting->plugin = $plugin->id;
			$setting->key = $settings::class;
		}

		$setting->value = $settings->toArray();
		$setting->save();

		return true;
	}

	public static function loadSettings(Plugin $plugin, ConfiglessSettingsContract&Model $settings): void
	{
		$settingRecord = Setting::find()
			->where([
				'plugin' => $plugin->id,
				'key' => $settings::class,
			])
			->one();

		if (!$settingRecord) return;

		$setting = json_decode($settingRecord->value, true);
		$settings->load($setting, '');
	}
}