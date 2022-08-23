<?php

namespace ContentReactor\Core\Entity;

use craft\base\Model;

class Attribute extends Model
{
	public ?string $id;
	public ?string $class;
	public ?array $data;

	public function __set(string $property, mixed $value): void
	{
		$this->$property = $value;
	}
}
