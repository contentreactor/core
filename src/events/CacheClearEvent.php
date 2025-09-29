<?php
declare(strict_types=1);

namespace ContentReactor\Core\Events;

use ContentReactor\Core\Base\CacheClearInterface;
use yii\base\Event;

/**
 * @property CacheClearInterface[] $cacheClearers
 */
class CacheClearEvent extends Event
{
	public const EVENT_BEFORE_CACHE_CLEAR = 'beforeClearCache';

	/** @var CacheClearInterface[] */
	public array $cacheClearers = [];
}
