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
		try {
			$core = Core::getInstance();
			$developionPlugins = array_filter(
				Craft::$app->getPlugins()->getAllPlugins(),
				fn (CraftPlugin $plugin) => $plugin->getSettings() && ($plugin !== $core) && str_contains($plugin->id, 'developion')
			);
			$core->db->setPluginSetting($core, 'developionPlugins', array_unique(array_keys($developionPlugins)));
		} catch (\Throwable $th) {
			$this->stdout("Error\n");
			Craft::error($th->getTrace(), $core->id);
			return ExitCode::UNSPECIFIED_ERROR;
		}
		$this->stdout("Developion plugins index flushed.\n");
		return ExitCode::OK;
	}
}
