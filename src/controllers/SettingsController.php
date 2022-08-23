<?php

namespace ContentReactor\Core\controllers;

use Craft;
use craft\base\Model;
use craft\helpers\ArrayHelper;
use craft\helpers\UrlHelper;
use craft\web\Controller;
use craft\web\Response;
use ContentReactor\Core\Core;
use yii\web\NotFoundHttpException;

class SettingsController extends Controller
{
	public function actionIndex(): Response
	{
		$core = Core::getInstance();
		$contentreactorPlugins = $core->db->getPluginSetting($core, 'contentreactorPlugins');
		$navItems = ArrayHelper::map(
			$contentreactorPlugins,
			fn ($plugin) => $plugin,
			function ($pluginHandle) {
				$plugin = Craft::$app->getPlugins()->getPlugin($pluginHandle);
				return [
					'title' => substr($plugin->name, strlen('ContentReactor ')),
					'settings' => Core::getInstance()->plugins->getPluginSettings($plugin),
				];
			}
		);
		$crumbs = [
			['label' => Craft::t('app', 'Settings'), 'url' => UrlHelper::cpUrl('settings')],
			['label' => 'ContentReactor', 'url' => UrlHelper::cpUrl('contentreactor-core/settings')]
		];

		if (empty($contentreactorPlugins)) {
			return $this->renderTemplate('contentreactor-core/settings/empty', [
				'crumbs' => $crumbs,
			]);
		}

		$selectedItem = reset($contentreactorPlugins);

		return $this->renderTemplate('contentreactor-core/settings', [
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
