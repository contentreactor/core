<?php

namespace Developion\Core\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Table;

/**
 * Install migration.
 */
class Install extends Migration
{
	const PLUGINS = '{{%developion_plugins}}';

	/** @inheritDoc */
	public function safeUp()
	{
		$this->createTables();
		$this->addForeignKeys();
	}

	/** @inheritDoc */
	public function safeDown()
	{
		// return true;
	}

	/**
	 * Creates the tables.
	 */
	public function createTables()
	{
		$this->createTable(self::PLUGINS, [
			'plugin' => $this->string()->notNull(),
			'siteId' => $this->integer()->notNull(),
			'key' => $this->string()->notNull(),
			'value' => $this->text()->notNull(),
			'dateCreated' => $this->dateTime()->notNull(),
			'dateUpdated' => $this->dateTime()->notNull(),
			'uid' => $this->uid(),
			'PRIMARY KEY([[key]])',
		]);
	}

	public function addForeignKeys()
	{
		$this->addForeignKey(null, self::PLUGINS, ['siteId'], Table::SITES, ['id'], 'CASCADE', 'CASCADE');
	}
}
