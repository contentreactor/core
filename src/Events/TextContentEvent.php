<?php
declare(strict_types=1);

namespace ContentReactor\Core\Events;

use yii\base\Event;

/**
 * @property string[] $textBlocks
 */
class TextContentEvent extends Event
{
	public const EVENT_FILTER_TEXT_BLOCKS = 'filterTextBlocksEvent';

	public array $textBlocks = [];
}
