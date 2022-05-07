<?php

namespace Developion\Core\migrations;

use craft\db\Migration;
use craft\db\Table;

class Install extends Migration
{
	const PLUGINS = '{{%developion_plugins}}';

	public function safeUp()
	{
		$this->createTables();
		$this->addIndexes();
		$this->addForeignKeys();
	}

	public function safeDown()
	{
		$this->dropTables();
	}

	public function createTables(): void
	{
		$this->createTable(self::PLUGINS, [
			'plugin' => $this->string()->notNull(),
			'siteId' => $this->integer()->notNull(),
			'key' => $this->string()->notNull(),
			'value' => $this->text()->notNull(),
			'dateCreated' => $this->dateTime()->notNull(),
			'dateUpdated' => $this->dateTime()->notNull(),
			'uid' => $this->uid(),
		]);
	}

	public function addIndexes(): void
	{
		$this->createIndex(null, self::PLUGINS, ['siteId', 'key'], true);
	}

	public function addForeignKeys(): void
	{
		$this->addForeignKey(null, self::PLUGINS, ['siteId'], Table::SITES, ['id'], 'CASCADE', 'CASCADE');
	}

	public function dropTables(): void
	{
		$this->dropTableIfExists(self::PLUGINS);
	}
}
