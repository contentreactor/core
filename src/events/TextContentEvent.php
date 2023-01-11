<?php

namespace ContentReactor\Core\events;

use yii\base\Event;

/**
 * @property string[] $textBlocks
 */
class TextContentEvent extends Event
{
	const EVENT_FILTER_TEXT_BLOCKS = 'filterTextBlocksEvent';

	public array $textBlocks = [];
}
