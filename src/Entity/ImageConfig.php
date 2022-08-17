<?php

namespace Contentreactor\Core\Entity;

use craft\base\Model;
use Contentreactor\Core\Entity\Casters\Attributes as AttributeCaster;
use Spatie\DataTransferObject\Attributes\CastWith;

class ImageConfig extends Model
{
	#[CastWith(AttributeCaster::class)]
	public array $attributes;
}
