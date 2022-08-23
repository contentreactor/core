<?php

namespace ContentReactor\Core\events;

use yii\base\Event;

class ContentReactorPluginEvent extends Event
{
	const EVENT_AT_PLUGIN_INIT = 'atPluginInit';

	/**
	 * @var callable[]
	 */
	public $callbacks = [];
}
