<?php

namespace Developion\Core\Services;

use Craft;
use craft\base\PluginInterface;
use craft\elements\Entry;
use craft\helpers\ArrayHelper;
use craft\web\UrlManager;
use Developion\Core\Records\Setting;

class DB
{
	public static array $slugs = [];

	public function getPluginSettings(PluginInterface $plugin, array $keys = []): array
	{
		$currentSite = Craft::$app->getSites()->getCurrentSite();
		$prefix = $plugin->id . '_';

		$path = Craft::$app->getPath()->getStoragePath() . "/{$plugin->getHandle()}-site-{$currentSite->id}.php";
		if (file_exists($path)) {
			$settings = require $path;
			if (!empty($settings)) {
				if (empty($keys)) return $settings;
				return array_filter(
					$settings,
					fn ($key) => in_array($key, $keys),
					ARRAY_FILTER_USE_KEY
				);
			}
		}

		$settings = Setting::find();
		foreach ($keys as $key) {
			$settings = $settings->orWhere("`key` = :key$key", ["key$key" => $prefix . $key]);
		}
		$settings = $settings->select(['key', 'value'])
			->andWhere([
				'plugin' => $plugin->id,
				'siteId' => $currentSite->id,
			]);
		$settings = ArrayHelper::map(
			$settings->all(),
			fn (Setting $setting) => substr($setting->key, strlen($prefix)),
			fn (Setting $setting) => unserialize($setting->value)
		);

		if (empty($settings)) {
			$settings = (array) $plugin->getSettings();
		}

		return $settings;
	}

	public function setPluginSettings(PluginInterface $plugin, array $settings): bool
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

	public function getPluginSettingsRaw(PluginInterface $plugin): array
	{
		return Setting::find()
			->where([
				'plugin' => $plugin->id,
			])
			->all();
	}

	public function getPluginSetting(PluginInterface $plugin, string $key): mixed
	{
		$currentSite = Craft::$app->getSites()->getCurrentSite();
		/** @var ?Setting $setting */
		$setting = Setting::find()
			->select(['key', 'value'])
			->where([
				'plugin' => $plugin->id,
				'siteId' => $currentSite->id,
				'key' => $plugin->id . '_' . $key,
			])
			->one();
		if ($setting === null) {
			settype($setting, gettype($plugin->getSettings()->$key));
			return $setting;
		}
		return unserialize($setting->value);
	}

	public function setPluginSetting(PluginInterface $plugin, string $key, mixed $value): mixed
	{
		$currentSite = Craft::$app->getSites()->getCurrentSite();
		$transaction = Craft::$app->getDb()->beginTransaction();
		try {
			$setting = Setting::findOne([
				'plugin' => $plugin->id,
				'siteId' => $currentSite->id,
				'key' => $plugin->id . '_' . $key
			]);
			if (!$setting) {
				$setting = new Setting([
					'plugin' => $plugin->id,
					'siteId' => $currentSite->id,
					'key' => $plugin->id . '_' . $key
				]);
			}
			if (gettype($plugin->getSettings()->$key) == 'array' && empty($value)) {
				$value = [];
			}
			settype($value, gettype($plugin->getSettings()->$key));
			$setting->value = serialize($value);
			$setting->save();
			$transaction->commit();
		} catch (\Throwable $th) {
			$transaction->rollBack();
			return false;
		}
		return true;
	}

	public function getSlug(string $entryType, string $section = 'page'): string
	{
		if (array_key_exists($section, static::$slugs) && is_array(static::$slugs[$section]) && array_key_exists($entryType, static::$slugs[$section])) {
			return static::$slugs[$section][$entryType];
		}
		/** @var PDO|false $connection */
		$connection = Craft::$app->getDb()->pdo;
		if (!$connection) return '';

		$query = "SELECT es.slug FROM (SELECT e.id AS elementsId, es.id AS elementsSitesId, c.id AS contentId FROM elements e INNER JOIN entries en ON en.id = e.id INNER JOIN elements_sites es ON es.elementId = e.id INNER JOIN content c ON c.elementId = e.id WHERE (en.typeId in(SELECT id FROM entrytypes WHERE handle=:type)) AND (en.sectionId in(SELECT id FROM sections WHERE handle=:section)) AND (e.archived=FALSE) AND (((e.enabled=TRUE) AND (es.enabled=TRUE))) AND (e.dateDeleted IS NULL) AND (e.draftId IS NULL) AND (e.revisionId IS NULL) ORDER BY en.postDate DESC LIMIT 1) subquery INNER JOIN elements_sites es ON es.id = subquery.elementsSitesId";
		$sql = $connection->prepare($query);
		$sql->execute([
			'type' => $entryType,
			'section' => $section
		]);
		$result = $sql->fetch($connection::FETCH_NUM);
		static::$slugs[$section][$entryType] = $result ? reset($result) : '';
		return static::$slugs[$section][$entryType];
	}

	public function matchEntry(array $config): Entry|null
	{
		/** @var UrlManager $urlManager*/
		$urlManager = Craft::$app->getUrlManager();
		$entry = $urlManager->getMatchedElement();
		if (!$entry) {
			$entryQuery = Entry::find();
			foreach ($config as $paramName => $paramValue) {
				$entryQuery = $entryQuery->$paramName($paramValue);
			}
			$entry = $entryQuery->one();
			$urlManager->setMatchedElement($entry);
		}
		return $entry;
	}
}
