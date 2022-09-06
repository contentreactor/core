<?php

namespace ContentReactor\Core\events;

use ContentReactor\Core\Entity\LinkField;
use yii\base\Event;

class LinkAttributesEvent extends Event
{
	const EVENT_BEFORE_RENDER_HTML_ATTRIBUTES = 'beforeRenderHtmlAttributes';

	public LinkField $linkField;

	public array $attributes;
}
