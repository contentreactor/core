<?php

namespace ContentReactor\Core\controllers;

use ContentReactor\Core\events\CacheClearEvent;
use Craft;
use craft\web\Controller;
use craft\helpers\FileHelper;
use craft\utilities\ClearCaches;
use ContentReactor\Core\Base\CacheClearInterface;
use InvalidArgumentException;
use Throwable;
use yii\base\Event;
use yii\caching\TagDependency;
use yii\web\Response;

class CacheController extends Controller
{
	protected array|int|bool $allowAnonymous = true;

	public $enableCsrfValidation = false;

	public function actionIndex(): Response
	{
		$pass = isset($_SERVER['PHP_AUTH_PW']) ? $_SERVER['PHP_AUTH_PW'] : '';
		$user = isset($_SERVER['PHP_AUTH_USER']) ? $_SERVER['PHP_AUTH_USER'] : '';
		if (!getenv('CLEAR_CACHE_PASS') || !getenv('CLEAR_CACHE_USER')) {
			return $this->asJson([
				'status' => 'error',
				'message' => "'CLEAR_CACHE' credentials not configured in project environment.",
			]);
		}
		if ($pass != getenv('CLEAR_CACHE_PASS') || $user != getenv('CLEAR_CACHE_USER')) {
			return $this->asJson([
				'status' => 'error',
				'message' => 'Invalid Credentials.',
			]);
		}

		$event = new CacheClearEvent([
			'cacheClearers' => [],
		]);
		Event::trigger(
			CacheClearEvent::class,
			CacheClearEvent::EVENT_BEFORE_CACHE_CLEAR,
			$event
		);

		foreach ($event->cacheClearers as $cacheClearer) {
			if ($cacheClearer instanceof CacheClearInterface) {
				$cacheClearer->clear();
			}
		}

		foreach (ClearCaches::cacheOptions() as $cacheOption) {
			$action = $cacheOption['action'];

			if (is_string($action)) {
				try {
					/** @throws InvalidArgumentException|Throwable */
					FileHelper::clearDirectory($action);
				} catch (InvalidArgumentException $e) {
					Craft::warning("Invalid directory {$action}: " . $e->getMessage(), __METHOD__);
				} catch (Throwable $e) {
					Craft::warning("Could not clear the directory {$action}: " . $e->getMessage(), __METHOD__);
				}
			} else if (isset($cacheOption['params'])) {
				call_user_func_array($action, $cacheOption['params']);
			} else {
				$action();
			}
		}

		$cache = Craft::$app->getCache();
		TagDependency::invalidate($cache, 'template');

		return $this->asJson([
			'status' => 'success'
		]);
	}
}
