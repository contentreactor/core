<?php

namespace Developion\Core\fields;

use Craft;
use craft\base\EagerLoadingFieldInterface;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\helpers\ArrayHelper;
use craft\validators\ArrayValidator;
use yii\db\Schema;

class Link extends Field implements EagerLoadingFieldInterface
{
	public array $allowedLinkTypes = [];

	public bool $textNotOptional = false;

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
			ArrayValidator::class,
			'min' => 1,
			'tooFew' => Craft::t('developion-core', 'You must select at least {min, number} of the {attribute}.'),
			'skipOnEmpty' => false
		];
		return $rules;
	}

	public function getContentColumnType(): array|string
	{
		$contentColumnTypes = [
			'text' => SCHEMA::TYPE_STRING,
			'linkType' => SCHEMA::TYPE_STRING,
			'target' => SCHEMA::TYPE_BOOLEAN,
		];

		if ($this->allowedLinkTypes === ['*']) {
			$allTypes = array_map(
				fn ($type) => $type['value'],
				$this->getAvailableLinkTypes()
			);
			foreach ($allTypes as $linkType) {
				$contentColumnTypes[$linkType] = SCHEMA::TYPE_STRING;
			}

			return $contentColumnTypes;
		}

		foreach ($this->allowedLinkTypes as $linkType) {
			$contentColumnTypes[$linkType] = SCHEMA::TYPE_STRING;
		}

		return $contentColumnTypes;
	}

	public function normalizeValue(mixed $value, ?ElementInterface $element = null): mixed
	{
		if (!$value) {
			$value = ArrayHelper::map(
				array_keys($this->getContentColumnType()),
				fn ($type) => $type,
				function ($type) {
					if ($type == 'target') {
						return false;
					}
					return '';
				}
			);
		}

		if (array_key_exists('asset', $value) && gettype($value['asset']) == 'string') {
			$value['asset'] = json_decode($value['asset']);
		}

		if (array_key_exists('entry', $value) && gettype($value['entry']) == 'string') {
			$value['entry'] = json_decode($value['entry']);
		}

		return $value;
	}

	protected function inputHtml(mixed $value, ElementInterface $element = null): string
	{
		return Craft::$app->getView()->renderTemplate("developion-core/_fields/link/input", [
			'value' => $value,
			'field' => $this,
		]);
	}

	public function getSettingsHtml(): ?string
	{
		return Craft::$app->getView()->renderTemplate("developion-core/_fields/link/settings", [
			'field' => $this,
		]);
	}

	public function getElementValidationRules(): array
	{
		return ['validateFieldStructure'];
	}

	public function validateFieldStructure(ElementInterface $element): void
	{
		/** @var Element $element */
		$value = $element->getFieldValue($this->handle);

		if (empty($value['text']) && $this->textNotOptional) {
			$element->addError("$this->handle", Craft::t('developion-core', 'The button text field can\'t be empty.'));
		}

		if ($value['linkType'] == 'entry' && empty($value['entry'])) {
			$element->addError($this->handle, Craft::t('developion-core', 'Entry can\'t be empty if the link type is Entry.'));
		}
		if ($value['linkType'] == 'asset' && empty($value['asset'])) {
			$element->addError($this->handle, Craft::t('developion-core', 'Asset can\'t be empty if the link type is Asset.'));
		}
		if ($value['linkType'] == 'url' && empty($value['url'])) {
			$element->addError($this->handle, Craft::t('developion-core', 'Url can\'t be empty if the link type is Url.'));
		}
		if ($value['linkType'] == 'email' && empty($value['email'])) {
			$element->addError($this->handle, Craft::t('developion-core', 'Email can\'t be empty if the link type is Email.'));
		}
		if ($value['linkType'] == 'phone' && empty($value['phone'])) {
			$element->addError($this->handle, Craft::t('developion-core', 'Phone can\'t be empty if the link type is Phone.'));
		}
	}

	public function getEagerLoadingMap(array $sourceElements): array|null|false
	{
		// dd($sourceElements);
		return [];
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

		return array_filter(
			$this->getAvailableLinkTypes(),
			fn ($type) => in_array($type['value'], $this->allowedLinkTypes)
		);
	}
}
