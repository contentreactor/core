<?php
declare(strict_types=1);

namespace ContentReactor\Core\Entity;

use ContentReactor\Core\Entity\Casters\Attributes as AttributeCaster;
use craft\base\Model;
use Spatie\DataTransferObject\Attributes\CastWith;

class ImageConfig extends Model
{
	#[CastWith(AttributeCaster::class)]
	public array $attributes;
}
