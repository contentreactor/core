<?php
declare(strict_types=1);

namespace ContentReactor\Core\Records;

use ContentReactor\Core\Migrations\Install;
use craft\base\Model;
use craft\db\ActiveRecord;
use craft\records\Site;
use yii\db\ActiveQueryInterface;

/**
 * Class Plugin Setting record.
 *
 * @property string $plugin Plugin
 * @property int $siteId Site ID
 * @property class-string<Model> $key Configless setting model class name
 * @property string $value Setting Value
 */
class Setting extends ActiveRecord
{
	public static function tableName(): string
	{
		return Install::PLUGINS;
	}

	public function rules(): array
	{
		return [
			[['plugin', 'key', 'siteId'], 'required'],
			[['plugin', 'key', 'value'], 'string', 'skipOnEmpty' => false],
			[['siteId'], 'number', 'integerOnly' => true],
			[['key', 'value'], 'safe'],
		];
	}

	public function getSite(): ActiveQueryInterface
	{
		return $this->hasOne(Site::class, ['id' => 'siteId']);
	}
}
