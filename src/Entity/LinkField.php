<?php

namespace ContentReactor\Core\Entity;

use ContentReactor\Core\events\LinkAttributesEvent;
use craft\elements\Asset;
use craft\elements\Entry;
use Spatie\DataTransferObject\DataTransferObject;
use yii\base\Event;
use craft\base\Model;

class LinkField extends Model
{
	public ?string $text;
	public ?string $linkType;
	public ?bool $target;
	public ?int $entry;
	public ?int $asset;
	public ?string $url;
	public ?string $youtube;
	public ?string $phone;
	public ?string $email;

	public array $tabs = [];

	public function getUrl(): string
	{
		$return = match ($this->linkType) {
			'entry' => !empty($this->entry) ? Entry::find()->id($this->entry)->one()?->url : '',
			'asset' => !empty($this->asset) ? Asset::find()->id($this->asset)->one()?->getUrl() : '',
			'phone' => "tel:{$this->phone}",
			'email' => "mailto:{$this->email}",
			'url' => $this->url,
			'youtube' => "https://www.youtube.com/watch?v={$this->youtube}",
			default => '',
		};

		return $return ?? '';
	}

	public function getHtmlAttributes(): array
	{
		$event = new LinkAttributesEvent([
			'attributes' => [],
			'linkField' => $this,
		]);

		Event::trigger(
			LinkAttributesEvent::class,
			LinkAttributesEvent::EVENT_BEFORE_RENDER_HTML_ATTRIBUTES,
			$event,
		);

		return $event->attributes;
	}

	public function toArray(array $fields = [], array $expand = [], $recursive = true): array
	{
		$value = parent::toArray();

		$value['entry'] = !empty($value['entry']) ? Entry::find()->id($value['entry'])?->one()?->id : null;
		$value['asset'] = !empty($value['asset']) ? Asset::find()->id($value['asset'])?->one()?->id : null;

		return $value;
	}
}
