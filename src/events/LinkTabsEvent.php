<?php

namespace ContentReactor\Core\events;

use ContentReactor\Core\Entity\LinkField;
use craft\base\Model;
use yii\base\Event;

/**
 * @property LinkField $linkField
 * @property Model[] $tabs
 */
class LinkTabsEvent extends Event
{
	const EVENT_LINK_TABS = 'linkTabsEvents';

	public LinkField $linkField;

	public array $tabs;
}
