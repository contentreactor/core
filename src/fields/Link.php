<?php

namespace Developion\Core\fields;

use Craft;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\elements\Asset;
use craft\elements\Entry;
use craft\helpers\Json;
use craft\validators\ArrayValidator;
use Developion\Core\Entity\LinkField;
use yii\db\Schema;

class Link extends Field
{
	public array $allowedLinkTypes = [];

	public bool $textNotOptional = true;

	private string $_errorMessage = 'The field couldn\'t be saved.';

	public static function displayName(): string
	{
		return Craft::t('developion-core', 'Link');
	}

	public function attributeLabels(): array
	{
		return [
			'allowedLinkTypes' => Craft::t('developion-core', 'Allowed Link Types'),
			'textNotOptional' => Craft::t('developion-core', 'Is link text mandatory?'),
		];
	}

	protected function defineRules(): array
	{
		$rules = parent::defineRules();
		$rules[] = [
			['allowedLinkTypes'],
			ArrayValidator::class ,
			'min' => 1,
			'tooFew' => Craft::t('developion-core', 'You must select at least {min, number} of the {attribute}.'),
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
		
		if (!is_array($value)) {
			$value = $this->_default();
		}
		
		if (empty($value['linkType'])) {
			$value['linkType'] = $this->getAllowedLinkTypes()[0]['value'];
		}
		
		$selectedValue = $value[$value['linkType']];
		foreach ($this->getAllowedLinkTypes() as $type) {
			$value[$type['value']] = $this->_default()[$type['value']];
		}
		$value[$value['linkType']] = is_string($value[$value['linkType']]) ? $selectedValue : (array)$selectedValue;

		$entryQuery = Entry::find();
		if (in_array('entry', array_column($this->getAllowedLinkTypes(), 'value'))) {
			if ($value['linkType'] == 'entry') $entryQuery = $entryQuery->id(reset($value['entry']));
		}
		$value['entry'] = $entryQuery;

		$assetQuery = Asset::find();
		if (in_array('asset', array_column($this->getAllowedLinkTypes(), 'value'))) {
			if ($value['linkType'] == 'entry') $assetQuery = $assetQuery->id(reset($value['asset']));
		}
		$value['asset'] = $assetQuery;

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
		return Craft::$app->getView()->renderTemplate('developion-core/_fields/link/input', [
			'value' => $value,
			'field' => $this,
		]);
	}

	public function getSettingsHtml(): ?string
	{
		return Craft::$app->getView()->renderTemplate('developion-core/_fields/link/settings', [
			'field' => $this,
		]);
	}

	public function getElementValidationRules(): array
	{
		return ['validateFieldStructure'];
	}

	public function validateFieldStructure(ElementInterface $element): void
	{
		/** @var LinkField $value */
		$value = $element->getFieldValue($this->handle);

		if (empty($value->text) && $this->textNotOptional) {
			$this->addError('text', $this->_getErrors('text'));
		}
		if ($value->linkType == 'entry' && !$value->entry->one()) {
			$this->addError('entry', $this->_getErrors('entry'));
		}
		if ($value->linkType == 'asset' && !$value->asset->one()) {
			$this->addError('asset', $this->_getErrors('asset'));
		}
		if ($value->linkType == 'url' && empty($value->url)) {
			$this->addError('url', $this->_getErrors('url'));
		}
		if ($value->linkType == 'email' && empty($value->email)) {
			$this->addError('email', $this->_getErrors('email'));
		}
		if ($value->linkType == 'phone' && empty($value->phone)) {
			$this->addError('phone', $this->_getErrors('phone'));
		}

		if ($this->hasErrors()) {
			$element->addError($this->handle, $this->_errorMessage);
			$element->addModelErrors($this, $this->handle);
		}
	}

	public function getAvailableLinkTypes(): array
	{
		return [
			['value' => 'entry', 'label' => 'Entry'],
			['value' => 'asset', 'label' => 'Asset'],
			['value' => 'url', 'label' => 'Url'],
			['value' => 'phone', 'label' => 'Phone'],
			['value' => 'email', 'label' => 'Email'],
		];
	}

	public function getAllowedLinkTypes(): array
	{
		if ($this->allowedLinkTypes === ['*']) {
			return $this->getAvailableLinkTypes();
		}

		return array_filter($this->getAvailableLinkTypes(), fn($type) => in_array($type['value'], $this->allowedLinkTypes));
	}

	private function _getErrors(string $attribute): string
	{
		$errors = [
			'text' => Craft::t('developion-core', 'The link text field can\'t be empty.'),
			'entry' => Craft::t('developion-core', 'Entry can\'t be empty if the link type is Entry.'),
			'asset' => Craft::t('developion-core', 'Asset can\'t be empty if the link type is Asset.'),
			'url' => Craft::t('developion-core', 'Url can\'t be empty if the link type is Url.'),
			'email' => Craft::t('developion-core', 'Email can\'t be empty if the link type is Email.'),
			'phone' => Craft::t('developion-core', 'Phone can\'t be empty if the link type is Phone.'),
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
			'entry' => [0],
			'asset' => [0],
			'url' => '',
			'phone' => '',
			'email' => '',
		];
	}
}
