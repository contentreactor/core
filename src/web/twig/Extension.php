<?php

namespace ContentReactor\Core\web\twig;

use ContentReactor\Core\events\TextContentEvent;
use Craft;
use craft\elements\Entry;
use craft\helpers\UrlHelper;
use ContentReactor\Core\web\twig\node\expression\ConstOperator;
use craft\helpers\StringHelper;
use GuzzleHttp\Client;
use Illuminate\Support\Collection;
use Symfony\Component\VarDumper\VarDumper;
use Twig\ExpressionParser;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;
use yii\base\Event;

class Extension extends AbstractExtension implements GlobalsInterface
{
	public function getName(): string
	{
		return 'Core';
	}

	public function getFunctions(): array
	{
		return [
			new TwigFunction('baseUrl', [UrlHelper::class, 'rootRelativeUrl']),
			new TwigFunction('dd', [$this, 'ddFunction']),
			new TwigFunction('fetch', [$this, 'fetchFunction']),
		];
	}

	public function getFilters(): array
	{
		return [
			new TwigFilter('first', [$this, 'firstFilter']),
			new TwigFilter('mapNeo', [$this, 'mapNeoFilter']),
			new TwigFilter('readTime', [$this, 'readTimeFilter']),
			new TwigFilter('slugify', [$this, 'slugifyFilter']),
			new TwigFilter('splice', [$this, 'spliceFilter']),
			new TwigFilter('uncamel', [$this, 'uncamelFilter']),

			// type casts
			new TwigFilter('array', fn (mixed $var): array => (array) $var),
			new TwigFilter('toArray', fn (Collection $array): array => $array->all()),
			new TwigFilter('int', fn (mixed $var): int => (int) $var),
			new TwigFilter('float', fn (mixed $var): float => (float) $var),
			new TwigFilter('string', fn (mixed $var): string => (string) $var),
			new TwigFilter('bool', fn (mixed $var): bool => (bool) $var),
		];
	}

	public function getOperators(): array
	{
		return [
			[],
			[
				'::' => [
					'precedence' => 500,
					'class' => ConstOperator::class,
					'associativity' => ExpressionParser::OPERATOR_LEFT
				]
			]
		];
	}

	public function getTests(): array
	{
		return [
			'instanceof' =>  new TwigTest('instanceof', [$this, 'instanceofTest']),
		];
	}

	public function ddFunction(mixed ...$vars): void
	{
		foreach ($vars as $v) {
			VarDumper::dump($v);
		}

		exit(1);
	}

	public function fetchFunction(string $baseUrl, string $endpoint, array $config = []): array
	{
		$config = array_merge([
			'method' => 'GET',
			'options' => [
				'headers' => [
					'Content-Type' => 'application/json',
					'Accept' => 'application/json'
				]
			],
		], $config);
		$method = 'GET';
		$options = [];
		extract($config);

		$client = new Client([
			'base_uri' => $baseUrl,
			'timeout' => 10
		]);

		try {
			$response = json_decode(
				$client->request($method, $endpoint, $options)
					->getBody(),
				true,
			);
		} catch (\Exception $e) {
			return [];
		}

		return $response;
	}

	public function firstFilter(array $array): mixed
	{
		return reset($array);
	}
	
	public function mapNeoFilter(array|Collection $array): array
	{
		$output = [];
		foreach ($array as $value) {
			$key = is_string($value->type) ? $value->handle : $value->type->handle;
			if (!isset($output[$key])) {
				$output[$key] = $value;
				continue;
			}
			if (!is_array($output[$key])) $output[$key] = [$output[$key]];
			$output[$key][] = $value;
		}
		return $output;
	}

	public function readTimeFilter(Entry $entry, string $fieldHandle = 'blogContent', bool $onlyNumber = false): string
	{
		$contentBlocks = $entry->$fieldHandle->all();
		$event = new TextContentEvent(['textBlocks' => [
			'text',
			'richText',
		]]);

		Event::trigger(
			TextContentEvent::class,
			TextContentEvent::EVENT_FILTER_TEXT_BLOCKS,
			$event,
		);

		$content = '';

		foreach ($contentBlocks as $contentBlock) {
			foreach ($event->textBlocks as $textBlock) {
				$content .= ($contentBlock->$textBlock ?? '') . ' ';
			}
		}

		$word = str_word_count(strip_tags($content));
		$est = round($word / 200);
		$readingTime = Craft::t('contentreactor-core', 'minutes of reading time');
		return $est . ($onlyNumber ? "" : " $readingTime");
	}

	public function slugifyFilter(string $str): string
	{
		return StringHelper::slugify($str);
	}

	public function spliceFilter(array $array, int $offset, ?int $length = null, array $replacement = []): array
	{
		array_splice($array, $offset, $length, $replacement);
		return $array;
	}

	public function uncamelFilter(string $string): string
	{
		$data = preg_split('/(?=[A-Z])/', $string);
		$string = implode(' ', $data);

		return ucwords($string);
	}

	public function getGlobals(): array
	{
		return [];
	}

	public function instanceofTest(mixed $var, string $className): bool
	{
		return $var instanceof $className;
	}
}
