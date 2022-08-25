<?php

namespace ContentReactor\Core\events;

use ContentReactor\Core\Base\CacheClearInterface;
use yii\base\Event;

/**
 * @property CacheClearInterface[] $cacheClearers
 */
class CacheClearEvent extends Event
{
	const EVENT_BEFORE_CACHE_CLEAR = 'beforeClearCache';

	public $cacheClearers = [];
}
