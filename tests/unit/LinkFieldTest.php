<?php

namespace ContentReactor\Core\tests\unit;

use Codeception\Test\Unit;
use ContentReactor\Core\Core;
use ContentReactor\Core\fields\Link as LinkField;
use UnitTester;
use Craft;
use craft\base\Field;
use craft\base\Model;
use craft\base\Plugin;
use Symfony\Component\VarDumper\VarDumper;

class LinkFieldTest extends Unit
{
	/**
	 * @var UnitTester
	 */
	protected $tester;

	public function testProjectHasLinkField(): void
	{
		$this->assertInstanceOf(
			Field::class,
			Craft::$app->getFields()->getFieldByHandle('linkField'),
		);
	}

	public function testLinkFieldIsCraftModelInstance(): void
	{
		/** @var LinkField */
		$field = Craft::$app->getFields()->getFieldByHandle('linkField');
		$this->assertInstanceOf(
			Model::class,
			$field->normalizeValue([]),
		);
	}

	public function testLinkFieldHasYoutubeOption(): void
	{
		/** @var LinkField */
		$field = Craft::$app->getFields()->getFieldByHandle('linkField');
		$this->assertContains(
			'youtube',
			array_column($field->getAvailableLinkTypes(), 'value'),
		);
	}
}
