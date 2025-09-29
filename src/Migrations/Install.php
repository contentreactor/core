<?php
declare(strict_types=1);

namespace ContentReactor\Core\Migrations;

use craft\db\{
	Migration,
	Table,
};

class Install extends Migration
{
	public const PLUGINS = '{{%contentreactor_plugins}}';

	public function safeUp(): void
	{
		$this->createTables();
		$this->addIndexes();
		$this->addForeignKeys();
	}

	public function safeDown(): void
	{
		$this->dropTables();
	}

	public function createTables(): void
	{
		if (!$this->db->tableExists(self::PLUGINS)) {
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
