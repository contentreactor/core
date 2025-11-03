<?php
declare(strict_types=1);

namespace ContentReactor\Core\Events;

use ContentReactor\Core\Entity\LinkField;
use yii\base\Event;

class LinkAttributesEvent extends Event
{
	public const EVENT_BEFORE_RENDER_HTML_ATTRIBUTES = 'beforeRenderHtmlAttributes';

	public LinkField $linkField;

	public array $attributes;
}
