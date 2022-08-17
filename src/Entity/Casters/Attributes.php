<?php

namespace Contentreactor\Core\Entity\Casters;

use Contentreactor\Core\Entity\Attribute;
use Exception;
use Spatie\DataTransferObject\Caster;

class Attributes implements Caster
{
	public function cast(mixed $value): array
	{
		if (!is_array($value)) {
			throw new Exception("Can only cast arrays to Attribute");
		}

		return array_map(
			fn (array $data) => new Attribute(...$data),
			$value
		);
	}
}
