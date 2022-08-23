<?php

namespace ContentReactor\Core\events;

use yii\base\Event;

class DefineDefaultColorsEvent extends Event
{
	public ?string $defaultTextColor = null;
	public ?string $defaultTextHoverColor = null;
	public ?string $defaultBackgroundColor = null;
	public ?string $defaultBackgroundHoverColor = null;
}