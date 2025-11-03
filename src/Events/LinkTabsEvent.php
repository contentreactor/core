<?php
declare(strict_types=1);

namespace ContentReactor\Core\Events;

use ContentReactor\Core\Entity\LinkField;
use craft\base\Model;
use yii\base\Event;

/**
 * @property LinkField $linkField
 * @property Model[] $tabs
 */
class LinkTabsEvent extends Event
{
	public const EVENT_LINK_TABS = 'linkTabsEvents';

	public LinkField $linkField;

	public array $tabs;
}
