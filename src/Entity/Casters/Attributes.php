<?php

namespace ContentReactor\Core\Entity\Casters;

use ContentReactor\Core\Entity\Attribute;
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
