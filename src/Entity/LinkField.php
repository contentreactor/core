<?php

namespace Developion\Core\Entity;

use craft\elements\Asset;
use craft\elements\Entry;
use Spatie\DataTransferObject\DataTransferObject;

class LinkField extends DataTransferObject
{
	public ?string $text;
	public ?string $linkType;
	public ?bool $target;
	public ?int $entry;
	public ?int $asset;
	public ?string $url;
	public ?string $phone;
	public ?string $email;

	public function getUrl(): string
	{
		$return = match ($this->linkType) {
			'entry' => !empty($this->entry) ? Entry::find()->id($this->entry)->one()?->url : '',
			'asset' => !empty($this->asset) ? Asset::find()->id($this->asset)->one()?->getUrl() : '',
			'phone' => "tel:{$this->phone}",
			'email' => "mailto:{$this->email}",
			'url' => $this->url,
			default => '',
		};

		return $return ?? '';
	}

	public function toArray(): array
	{
		$value = parent::toArray();

		$value['entry'] = !empty($value['entry']) ? Entry::find()->id($value['entry'])?->one()?->id : null;
		$value['asset'] = !empty($value['asset']) ? Asset::find()->id($value['asset'])?->one()?->id : null;

		return $value;
	}
}
