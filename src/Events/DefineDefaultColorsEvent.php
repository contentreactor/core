<?php
declare(strict_types=1);

namespace ContentReactor\Core\Events;

use yii\base\Event;

class DefineDefaultColorsEvent extends Event
{
	public ?string $defaultTextColor = null;
	public ?string $defaultTextHoverColor = null;
	public ?string $defaultBackgroundColor = null;
	public ?string $defaultBackgroundHoverColor = null;
}