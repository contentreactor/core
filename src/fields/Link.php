<?php

namespace ContentReactor\Core\fields;

use Craft;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\helpers\Json;
use craft\validators\ArrayValidator;
use ContentReactor\Core\Entity\LinkField;
use ContentReactor\Core\events\LinkTabsEvent;
use craft\base\Element;
use yii\base\Event;
use yii\db\Schema;

class Link extends Field
{
	public array $allowedLinkTypes = [];

	public bool $textNotOptional = true;

	public bool $urlNotOptional = true;

	private string $_errorMessage = 'The field couldn\'t be saved.';

	public static function displayName(): string
	{
		return Craft::t('contentreactor-core', 'Link');
	}

	public function attributeLabels(): array
	{
		return [
			'allowedLinkTypes' => Craft::t('contentreactor-core', 'Allowed Link Types'),
			'textNotOptional' => Craft::t('contentreactor-core', 'Is the link text mandatory?'),
			'urlNotOptional' => Craft::t('contentreactor-core', 'Is the URL mandatory?'),
			'text' => Craft::t('contentreactor-core', 'Text'),
			'target' => Craft::t('contentreactor-core', 'Target'),
			'linkType' => Craft::t('contentreactor-core', 'Link Type'),
			'entry' => Craft::t('contentreactor-core', 'Entry'),
			'asset' => Craft::t('contentreactor-core', 'Asset'),
			'url' => Craft::t('contentreactor-core', 'URL'),
			'youtube' => Craft::t('contentreactor-core', 'YouTube video ID'),
			'phone' => Craft::t('contentreactor-core', 'Phone'),
			'email' => Craft::t('contentreactor-core', 'Email'),
		];
	}

	protected function defineRules(): array
	{
		$rules = parent::defineRules();
		$rules[] = [
			['allowedLinkTypes'],
			ArrayValidator::class,
			'min' => 1,
			'tooFew' => Craft::t('contentreactor-core', 'You must select at least {min, number} of the {attribute}.'),
			'skipOnEmpty' => false
		];
		return $rules;
	}

	public static function valueType(): string
	{
		return LinkField::class;
	}

	public function getContentColumnType(): array |string
	{
		return SCHEMA::TYPE_TEXT;
	}

	public function normalizeValue(mixed $value, ?ElementInterface $element = null): mixed
	{
		if ($value instanceof LinkField) {
			return $value;
		}

		if (is_string($value) && !empty($value)) {
			$value = Json::decodeIfJson($value);
		}

		if (!is_array($value) || empty($value)) {
			$value = $this->_default();
		}

		if (!empty(array_intersect_key($value, $this->_default()))) {
			$value = array_merge($this->_default(), $value);
		}

		$allowedLinkTypes = array_column($this->getAllowedLinkTypes(), 'value');
		if (empty($value['linkType'])) {
			$value['linkType'] = reset($allowedLinkTypes);
		}

		$selectedValue = $value[$value['linkType']];
		foreach ($allowedLinkTypes as $type) {
			$value[$type] = $this->_default()[$type];
		}
		$value[$value['linkType']] = empty($selectedValue) ? null : (is_array($selectedValue) ? reset($selectedValue) : $selectedValue);

		return new LinkField($value);
	}

	public function serializeValue(mixed $value, ?ElementInterface $element = null): mixed
	{
		if ($value instanceof LinkField) {
			return $value->toArray();
		}
		return $value;
	}

	protected function inputHtml(mixed $value, ElementInterface $element = null): string
	{
		$tabs = [];
		$event = new LinkTabsEvent([
			'linkField' => $value,
			'tabs' => $tabs,
		]);
		Event::trigger(
			LinkTabsEvent::class,
			LinkTabsEvent::EVENT_LINK_TABS,
			$event,
		);
		$tabs = $event->tabs;


		return Craft::$app->getView()->renderTemplate('contentreactor-core/_fields/link/input', [
			'value' => $value,
			'field' => $this,
			'tabs' => $tabs,
			'ownerId' => $element?->id,
		]);
	}

	public function getSettingsHtml(): ?string
	{
		return Craft::$app->getView()->renderTemplate('contentreactor-core/_fields/link/settings', [
			'field' => $this,
		]);
	}

	public function getElementValidationRules(): array
	{
		return [
			[
				'validateFieldStructure',
				'on' => [Element::SCENARIO_LIVE],
				'skipOnEmpty' => false,
			],
		];
	}

	public function validateFieldStructure(ElementInterface $element): void
	{
		/** @var LinkField $value */
		$value = $element->getFieldValue($this->handle);

		if (empty($value->text) && $this->textNotOptional) {
			$this->addError('text', $this->_getErrors('text'));
		}
		if ($value->linkType == 'entry' && empty($value->entry)) {
			$this->addError('entry', $this->_getErrors('entry'));
		}
		if ($value->linkType == 'asset' && empty($value->asset)) {
			$this->addError('asset', $this->_getErrors('asset'));
		}
		if ($value->linkType == 'url' && $this->urlNotOptional && empty($value->url)) {
			$this->addError('url', $this->_getErrors('url'));
		}
		if ($value->linkType == 'youtube' && empty($value->youtube)) {
			$this->addError('youtube', $this->_getErrors('youtube'));
		}
		if ($value->linkType == 'email' && empty($value->email)) {
			$this->addError('email', $this->_getErrors('email'));
		}
		if ($value->linkType == 'phone' && empty($value->phone)) {
			$this->addError('phone', $this->_getErrors('phone'));
		}

		if ($this->hasErrors()) {
			$element->addError($this->handle, Craft::t('contentreactor-core', $this->_errorMessage));
			$element->addModelErrors($this, $this->handle);
		}
	}

	public function getAvailableLinkTypes(): array
	{
		return [
			['value' => 'entry', 'label' => Craft::t('contentreactor-core', 'Entry')],
			['value' => 'asset', 'label' => Craft::t('contentreactor-core', 'Asset')],
			['value' => 'url', 'label' => Craft::t('contentreactor-core', 'Url')],
			['value' => 'youtube', 'label' => Craft::t('contentreactor-core', 'YouTube')],
			['value' => 'phone', 'label' => Craft::t('contentreactor-core', 'Phone')],
			['value' => 'email', 'label' => Craft::t('contentreactor-core', 'Email')],
		];
	}

	public function getAllowedLinkTypes(): array
	{
		if ($this->allowedLinkTypes === ['*']) {
			return $this->getAvailableLinkTypes();
		}

		return array_filter(
			$this->getAvailableLinkTypes(),
			fn ($type) => in_array($type['value'], $this->allowedLinkTypes)
		);
	}

	private function _getErrors(string $attribute): string
	{
		$errors = [
			'text' => Craft::t('contentreactor-core', 'The link text field can\'t be empty.'),
			'entry' => Craft::t('contentreactor-core', 'Entry can\'t be empty if the link type is Entry.'),
			'asset' => Craft::t('contentreactor-core', 'Asset can\'t be empty if the link type is Asset.'),
			'url' => Craft::t('contentreactor-core', 'URL can\'t be empty if the URL content is mandatory.'),
			'youtube' => Craft::t('contentreactor-core', 'Video ID can\'t be empty if the link type is YouTube.'),
			'email' => Craft::t('contentreactor-core', 'Email can\'t be empty if the link type is Email.'),
			'phone' => Craft::t('contentreactor-core', 'Phone can\'t be empty if the link type is Phone.'),
		];

		if (!array_key_exists($attribute, $errors))
			return '';

		return $errors[$attribute];
	}

	private function _default(): array
	{
		return [
			'text' => '',
			'target' => false,
			'linkType' => '',
			'entry' => null,
			'asset' => null,
			'url' => '',
			'youtube' => '',
			'phone' => '',
			'email' => '',
		];
	}
}
