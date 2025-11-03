<?php
declare(strict_types=1);

namespace ContentReactor\Core\Events;

use yii\base\Event;

class ContentReactorPluginEvent extends Event
{
	public const EVENT_AT_PLUGIN_INIT = 'atPluginInit';

	/**
	 * @var callable[]
	 */
	public $callbacks = [];
}
