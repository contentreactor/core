<?php

namespace Developion\Core\events;

use yii\base\Event;

class DevelopionPluginEvent extends Event
{
	const EVENT_AT_PLUGIN_INIT = 'atPluginInit';

	/**
	 * @var callable[]
	 */
	public $callbacks = [];
}
