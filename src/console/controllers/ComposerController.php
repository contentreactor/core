<?php

namespace Developion\Core\console\controllers;

use Craft;
use craft\base\Plugin as CraftPlugin;
use craft\console\Controller;
use Developion\Core\Core;
use yii\console\ExitCode;

class ComposerController extends Controller
{
	public function actionIndex(): int
	{
		$transaction = Craft::$app->getDb()->beginTransaction();
		try {
			$core = Core::getInstance();
			$projectConfig = Craft::$app->getProjectConfig();
			$allPluginsConfig = array_filter(
				array_keys($projectConfig->get('plugins')),
				fn ($handle) => str_contains($handle, 'developion'),
			);
			$developionPlugins = [];

			foreach ($allPluginsConfig as $pluginHandle) {
				$plugin = Craft::$app->getPlugins()->getPlugin($pluginHandle);
				if (!$plugin) {
					$projectConfig->remove("plugins.$pluginHandle");
					continue;
				}
				if ($plugin->getSettings() && $plugin !== $core) {
					$developionPlugins[] = $plugin;
				}
			}
			$developionPlugins = array_unique(array_map(fn ($plugin) => $plugin->id, $developionPlugins));

			$core->db->setPluginSetting($core, 'developionPlugins', $developionPlugins);
			$transaction->commit();
		} catch (\Throwable $th) {
			$transaction->rollBack();
			$this->stdout("Error\n");
			Craft::error($th->getTrace(), $core->id);
			return ExitCode::UNSPECIFIED_ERROR;
		}
		$this->stdout("Developion plugins index flushed.\n");
		return ExitCode::OK;
	}
}
