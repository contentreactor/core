<?php

namespace Developion\Core;

use Craft;
use craft\base\Plugin;
use craft\console\Application;
use craft\events\RegisterCacheOptionsEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterTemplateRootsEvent;
use craft\helpers\FileHelper;
use craft\helpers\Queue;
use craft\i18n\PhpMessageSource;
use craft\services\Fields;
use craft\utilities\ClearCaches;
use craft\web\View;
use Developion\Core\events\DeleteRenderedContentEvent;
use Developion\Core\fields\InceptionMatrix;
use Developion\Core\jobs\RenderContent;
use Developion\Core\services\ImagesService;
use Developion\Core\web\twig\Extension;
use yii\base\Event;

class Core extends Plugin
{
	public static $plugin;

	public function init()
	{
		parent::init();
		self::$plugin = $this;
		
		$this->setComponents([
			'images' => ImagesService::class,
		]);

		$request = Craft::$app->getRequest();
		if ($request->getIsCpRequest()) {
			$this->_cpEvents();
		} elseif ($request->getIsConsoleRequest()) {
			$this->_consoleEvents();
		} else {
			$this->_siteEvents();
		}
		$this->_twigExtensions();

		Craft::$app->i18n->translations['core'] = [
			'class' => PhpMessageSource::class,
			'basePath' => __DIR__ . '/translations',
			'allowOverrides' => true,
			'forceTranslation' => true
		];
		
        Event::on(Fields::class, Fields::EVENT_REGISTER_FIELD_TYPES, function (RegisterComponentTypesEvent $event) {
            $event->types[] = InceptionMatrix::class;
        });
        
        if ( Craft::$app instanceof Application) {
			$this->controllerNamespace = 'Developion\Core\console\controllers';
		}
	}

	protected function _cpEvents()
	{
	}

	protected function _siteEvents()
	{
		Event::on(
			View::class,
			View::EVENT_REGISTER_SITE_TEMPLATE_ROOTS,
			function (RegisterTemplateRootsEvent $event) {
				$event->roots['developion-core'] = __DIR__ . '/templates';
			}
		);
	}

	protected function _consoleEvents()
	{
	}

	protected function _twigExtensions()
	{
		Craft::$app->view->registerTwigExtension(new Extension);
	}
}
