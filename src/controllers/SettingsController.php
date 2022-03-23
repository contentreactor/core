<?php

namespace Developion\Core\controllers;

use Craft;
use craft\base\Model;
use craft\helpers\ArrayHelper;
use craft\helpers\Cp;
use craft\helpers\UrlHelper;
use craft\web\Controller;
use Developion\Core\Core;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class SettingsController extends Controller
{
	public function actionIndex()
	{
		$core = Core::getInstance();
		$developionPlugins = $core->db->getPluginSetting($core, 'developionPlugins');
		$navItems = ArrayHelper::map(
			$developionPlugins,
			fn ($plugin) => $plugin,
			function ($pluginHandle) {
				$plugin = Craft::$app->getPlugins()->getPlugin($pluginHandle);
				return [
					'title' => substr($plugin->name, strlen('Developion ')),
					'settings' => Core::getInstance()->plugins->getPluginSettings($plugin),
				];
			}
		);
		$crumbs = [
			['label' => Craft::t('app', 'Settings'), 'url' => UrlHelper::cpUrl('settings')],
			['label' => 'Developion', 'url' => UrlHelper::cpUrl('developion-core/settings')]
		];
		$selectedItem = $developionPlugins[0];

		$this->renderTemplate('developion-core/settings', [
			'navItems' => $navItems,
			'selectedItem' => $selectedItem,
			'crumbs' => $crumbs,
		]);
	}

	public function actionSave(): Response|null
	{
		$this->requirePostRequest();
		$pluginHandle = $this->request->getRequiredBodyParam('pluginHandle');
		$settings = $this->request->getBodyParam('settings', []);
		$plugin = Craft::$app->getPlugins()->getPlugin($pluginHandle);

		if ($plugin === null) {
			throw new NotFoundHttpException('Plugin not found');
		}

		$scenario = $this->request->getBodyParam('scenario', Model::SCENARIO_DEFAULT);
		$plugin->getSettings()->setScenario($scenario);

		if (!Core::getInstance()->plugins->savePluginSettings($plugin, $settings)) {
			$this->setFailFlash(Craft::t('app', 'Couldn’t save plugin settings.'));
			Craft::$app->getUrlManager()->setRouteParams([
				'plugin' => $plugin,
			]);

			return null;
		}

		$this->setSuccessFlash(Craft::t('app', 'Plugin settings saved.'));
		return $this->redirectToPostedUrl();
	}
}
