<?php

namespace Contentreactor\Core\events;

use yii\base\Event;

class ContentreactorPluginEvent extends Event
{
	const EVENT_AT_PLUGIN_INIT = 'atPluginInit';

	/**
	 * @var callable[]
	 */
	public $callbacks = [];
}
