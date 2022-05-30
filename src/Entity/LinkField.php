<?php

namespace Developion\Core\Entity;

use craft\elements\db\ElementQuery;
use Spatie\DataTransferObject\DataTransferObject;

class LinkField extends DataTransferObject
{
	public ?string $text;
	public ?string $linkType;
	public ?bool $target;
	public ElementQuery $entry;
	public ElementQuery $asset;
	public ?string $url;
	public ?string $phone;
	public ?string $email;

	public function getUrl(...$args): string
	{
		switch ($this->linkType) {
			case 'entry': return $this->entry->one() ? $this->entry->one()->url : '';
			case 'asset': return $this->asset->one() ? $this->asset->one()->getUrl() : '';
			case 'phone': return "tel:{$this->phone}";
			case 'email': return "mailto:{$this->email}";
			case 'url': return $this->url;
		}
		return '';
	}

	public function toArray(): array
	{
		$array = parent::toArray();

		$array['entry'] = $array['entry']->one()?->id;
		$array['asset'] = $array['asset']->one()?->id;

		return $array;
	}
}
