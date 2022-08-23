<?php

namespace ContentReactor\Core\fields;

use Craft;
use craft\base\EagerLoadingFieldInterface;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\helpers\ArrayHelper;
use craft\validators\ArrayValidator;
use ContentReactor\Core\events\DefineDefaultColorsEvent;
use JetBrains\PhpStorm\ArrayShape;
use yii\base\Event;
use yii\db\Schema;
use craft\base\Element;

class Button extends Field
{
	const EVENT_DEFINE_DEFAULT_COLORS = 'defineDefaultColors';

	public ?string $defaultTextColor = null;

	public ?string $defaultTextHoverColor = null;

	public ?string $defaultBackgroundColor = null;

	public ?string $defaultBackgroundHoverColor = null;

	public array $allowedLinkTypes = [];

	public bool $textOptional = false;

	public static function isRequirable(): bool
	{
		return false;
	}

	public static function displayName(): string
	{
		return Craft::t('contentreactor-core', 'Button');
	}

	#[ArrayShape([
		'allowedLinkTypes' => "string",
		'defaultTextColor' => "string",
		'defaultTextHoverColor' => "string",
		'defaultBackgroundColor' => "string",
		'defaultBackgroundHoverColor' => "string"
	])]
	public function attributeLabels(): array
	{
		return [
			'allowedLinkTypes' => Craft::t('contentreactor-core', 'Allowed Link Types'),
			'defaultTextColor' => Craft::t('contentreactor-core', 'Default Text Color'),
			'defaultTextHoverColor' => Craft::t('contentreactor-core', 'Default Text Hover Color'),
			'defaultBackgroundColor' => Craft::t('contentreactor-core', 'Default Background Color'),
			'defaultBackgroundHoverColor' => Craft::t('contentreactor-core', 'Default Background Hover Color'),
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

	public function getContentColumnType(): array|string
	{
		$contentColumnTypes = [
			'text' => SCHEMA::TYPE_STRING,
			'linkType' => SCHEMA::TYPE_STRING,
			'target' => SCHEMA::TYPE_BOOLEAN,
			'textColor' => SCHEMA::TYPE_STRING,
			'textHoverColor' => SCHEMA::TYPE_STRING,
			'backgroundColor' => SCHEMA::TYPE_STRING,
			'backgroundHoverColor' => SCHEMA::TYPE_STRING,
			'tag' => SCHEMA::TYPE_STRING,
		];

		if ($this->allowedLinkTypes === ['*']) {
			$allTypes = array_map(
				fn($type) => $type['value'],
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
				fn($type) => $type,
				function($type) {
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
		return Craft::$app->getView()->renderTemplate("contentreactor-core/_fields/button/input", [
			'value' => $value,
			'field' => $this,
		]);
	}

	public function getSettingsHtml(): ?string
	{
		return Craft::$app->getView()->renderTemplate("contentreactor-core/_fields/button/settings", [
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

		if (empty($value['text'])) {
			$element->addError("$this->handle", Craft::t('contentreactor-core', 'The button text field can\'t be empty.'));
		}

		if (empty($value['tag'])) {
			$element->addError($this->handle, Craft::t('contentreactor-core', 'The button tag must be selected.'));
		}

		if ($value['linkType'] !== '-') {
			if ($value['linkType'] == 'entry' && empty($value['entry'])) {
				$element->addError($this->handle, Craft::t('contentreactor-core', 'Entry can\'t be empty if the link type is Entry.'));
			}
			if ($value['linkType'] == 'asset' && empty($value['asset'])) {
				$element->addError($this->handle, Craft::t('contentreactor-core', 'Asset can\'t be empty if the link type is Asset.'));
			}
			if ($value['linkType'] == 'url' && empty($value['url'])) {
				$element->addError($this->handle, Craft::t('contentreactor-core', 'Url can\'t be empty if the link type is Url.'));
			}
			if ($value['linkType'] == 'email' && empty($value['email'])) {
				$element->addError($this->handle, Craft::t('contentreactor-core', 'Email can\'t be empty if the link type is Email.'));
			}
			if ($value['linkType'] == 'phone' && empty($value['phone'])) {
				$element->addError($this->handle, Craft::t('contentreactor-core', 'Phone can\'t be empty if the link type is Phone.'));
			}
		}
	}

	public function beforeSave(bool $isNew): bool
	{
		if ($this->hasEventHandlers(self::EVENT_DEFINE_DEFAULT_COLORS)) {
			$event = new DefineDefaultColorsEvent([]);
			Event::trigger(
				self::class,
				self::EVENT_DEFINE_DEFAULT_COLORS,
				$event
			);
			$this->_parseDefaultColors($this->_parseColorsEvent($event));
		}
		return parent::beforeSave($isNew);
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
			fn($type) => in_array($type['value'], $this->allowedLinkTypes)
		);
	}

	private function _parseDefaultColors(...$args): void
	{
		if (is_array($args[0] ?? null)) {
			$args = $args[0];
		}
		$keys = array_keys($args);
		foreach ($keys as $key) {
			if (!$this->$key) {
				$this->$key = $args[$key];
			}
		}
	}

	#[ArrayShape(['defaultTextColor' => "null|string", 'defaultTextHoverColor' => "null|string", 'defaultBackgroundColor' => "null|string", 'defaultBackgroundHoverColor' => "null|string"])]
	private function _parseColorsEvent(DefineDefaultColorsEvent $event): array
	{
		return [
			'defaultTextColor' => $event->defaultTextColor,
			'defaultTextHoverColor' => $event->defaultTextHoverColor,
			'defaultBackgroundColor' => $event->defaultBackgroundColor,
			'defaultBackgroundHoverColor' => $event->defaultBackgroundHoverColor,
		];
	}
}
