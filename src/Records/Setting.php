<?php

namespace Contentreactor\Core\Records;

use craft\db\ActiveRecord;
use craft\records\Site;
use Contentreactor\Core\migrations\Install;
use yii\db\ActiveQueryInterface;

/**
 * Class Plugin Setting record.
 *
 * @property string $plugin Plugin
 * @property int $siteId Site ID
 * @property string $key Setting Key
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
			[['plugin', 'key', 'value'], 'string', 'max' => 255],
			[['siteId'], 'number', 'integerOnly' => true],
			[['key', 'value'], 'safe'],
		];
	}

	public function getSite(): ActiveQueryInterface
	{
		return $this->hasOne(Site::class, ['id' => 'siteId']);
	}
}
