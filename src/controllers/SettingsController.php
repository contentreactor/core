<?php

namespace Developion\Core\controllers;

use Craft;
use craft\web\Controller;
use Developion\Core\Core;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class SettingsController extends Controller
{
	/**
	 * @deprecated
	 * @return null|Response
	 */
	public function actionSaveSettings(): null|Response
	{
		$this->requirePostRequest();
		$pluginHandle = $this->request->getRequiredBodyParam('pluginHandle');
		$settings = $this->request->getBodyParam('settings', []);
		$plugin = Craft::$app->getPlugins()->getPlugin($pluginHandle);
		$pluginBaseSettings = $plugin->getSettings();

		foreach ($settings as $key => &$setting) {
			$setting = array_merge($pluginBaseSettings[$key], ['value' => $setting]);
		}

		if ($plugin === null) {
			throw new NotFoundHttpException('Plugin not found');
		}
		if (!Craft::$app->getPlugins()->savePluginSettings($plugin, $settings)) {
			$this->setFailFlash(Craft::t('app', 'Couldn’t save plugin settings.'));

			Craft::$app->getUrlManager()->setRouteParams([
				'plugin' => $plugin,
			]);

			return null;
		}

		$this->setSuccessFlash(Craft::t('app', 'Plugin settings saved.'));
		return $this->redirectToPostedUrl();
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
