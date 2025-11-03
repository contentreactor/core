<?php
declare(strict_types=1);

namespace ContentReactor\Core;

use ContentReactor\Core\Models\Settings;
use ContentReactor\Core\Services\{
	DB,
	Plugins,
};
use ContentReactor\Core\Web\Twig\Variables\ContentReactor as CRVariable;
use Craft;
use craft\events\{
	RegisterTemplateRootsEvent,
	RegisterUrlRulesEvent,
};
use craft\helpers\UrlHelper;
use craft\i18n\PhpMessageSource;
use craft\web\{
	Response,
	UrlManager,
	View,
};
use craft\web\twig\variables\CraftVariable;
use yii\base\Event;
use yii\base\Module;

/**
 * Class Core
 *
 * @package contentreactor/core
 *
 * @property Core $plugin
 * @property Plugins $plugins
 * @property DB $db
 */
class Core extends Module
{
	public const ID = 'Core';

	public function init(): void
	{
		parent::init();
		$this->id = self::ID;
		Craft::setAlias('@core', __DIR__);

		$request = Craft::$app->getRequest();
		if ($request->getIsConsoleRequest()) {
			$this->_consoleEvents();
		}

		$this->_config();
	}

	protected function _config(): void
	{
		Craft::$app->getI18n()->translations['site'] = [
			'class' => PhpMessageSource::class,
			'sourceLanguage' => 'en',
			'basePath' => '@core/translations',
			'forceTranslation' => true,
			'allowOverrides' => true,
		];
		$this->setComponents([
			'db' => DB::class,
			'plugins' => Plugins::class,
		]);

		Event::on(
			CraftVariable::class,
			CraftVariable::EVENT_INIT,
			static function (Event $event): void {
				$variable = $event->sender;
				$variable->set('cr', CRVariable::class);
			}
		);
	}

	public function getSettingsResponse(): Response
	{
		return Craft::$app->getResponse()->redirect(UrlHelper::cpUrl('contentreactor-core/settings'));
	}

	protected function _events(): void
	{
		Event::on(
			View::class,
			View::EVENT_REGISTER_SITE_TEMPLATE_ROOTS,
			static function (RegisterTemplateRootsEvent $event): void {
				$event->roots['contentreactor-core'] = __DIR__ . '/Templates';
			}
		);

		Event::on(
			UrlManager::class,
			UrlManager::EVENT_REGISTER_CP_URL_RULES,
			static function (RegisterUrlRulesEvent $event): void {
				$event->rules['contentreactor-core/settings/save'] = 'contentreactor-core/settings/save';
				$event->rules['contentreactor-core/settings'] = 'contentreactor-core/settings';
			}
		);

		Event::on(
			UrlManager::class,
			UrlManager::EVENT_REGISTER_SITE_URL_RULES,
			static function (RegisterUrlRulesEvent $event): void {
				$event->rules['POST cache/clear'] = 'contentreactor-core/cache';
			}
		);
	}

	protected function createSettingsModel(): Settings
	{
		return new Settings();
	}
}
