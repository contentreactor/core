<?php
declare (strict_types=1);

namespace ContentReactor\Core\Base;

use ContentReactor\Core\Web\Twig\Extension;
use Craft;
use craft\base\Plugin;
use craft\db\MigrationManager;
use ReflectionClass;
use Twig\Extension\AbstractExtension;

abstract class ContentReactorPlugin extends Plugin implements ContentReactorPluginInterface
{
	/** @var class-string<AbstractExtension>[] */
	public array $extensions = [];

	public function init(): void
	{
		parent::init();

		// $this->registerNamespaces();
		$this->registerExtensions();
	}

	final protected function registerNamespaces(): void
	{
		$ref = new ReflectionClass($this);
		$ns = $ref->getNamespaceName();
		if (!$this->getMigrator()) {
			$this->set('migrator', [
				'class' => MigrationManager::class,
				'track' => "plugin:$this->id",
				'migrationNamespace' => ($ns ? $ns . '\\' : '') . 'Migrations',
				'migrationPath' => $this->getBasePath() . DIRECTORY_SEPARATOR . 'Migrations',
			]);
		} else {
			$this->migrator->migrationNamespace = ($ns ? $ns . '\\' : '') . 'Migrations';
			$this->migrator->migrationPath = $this->getBasePath() . DIRECTORY_SEPARATOR . 'Migrations';
		}
	}

	final protected function registerExtensions(): void
	{
		Craft::$app->getView()->registerTwigExtension(new Extension);
		if (empty($this->extensions)) return;

		foreach ($this->extensions as $extensionClass) {
			Craft::$app->getView()->registerTwigExtension(new $extensionClass);
		}
	}
}
