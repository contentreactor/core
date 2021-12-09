<?php

namespace Developion\Core\web\twig;

use Craft;
use craft\elements\Entry;
use craft\helpers\UrlHelper;
use GuzzleHttp\Client;
use Symfony\Component\VarDumper\VarDumper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class Extension extends AbstractExtension
{
	/**
	 * Return our Twig Extension name
	 *
	 * @return string
	 */
	public function getName(): string
	{
		return 'Core';
	}

	/**
	 * Return our Twig functions
	 *
	 * @return array
	 */
	public function getFunctions(): array
	{
		return [
			new TwigFunction('baseUrl', [UrlHelper::class, 'rootRelativeUrl']),
			new TwigFunction('dd', [$this, 'ddFunction']),
			new TwigFunction('fetch', [$this, 'fetchFunction']),
		];
	}

	/**
	 * Return our Twig filters
	 *
	 * @return array
	 */
	public function getFilters(): array
	{
		return [
			new TwigFilter('htmlParse', [$this, 'htmlParseFilter']),
			new TwigFilter('readTime', [$this, 'readTimeFilter']),
			new TwigFilter('splice', [$this, 'spliceFilter']),
			new TwigFilter('uncamel', [$this, 'uncamelFilter']),
		];
	}

	public function ddFunction(...$vars)
	{
		foreach ($vars as $v) {
			VarDumper::dump($v);
		}

		exit(1);
	}

	/**
	 * Twig abstraction for Guzzlehttp.
	 *
	 * @param string $baseUrl
	 * @param string $endpoint
	 * @param array $config
	 * @return array
	 */
	public function fetchFunction($baseUrl, $endpoint, $config = [])
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

	/**
	 * Parse multimedia from html and extracts sources of images and video tags.
	 *
	 * @param string $html
	 * @return string
	 */
	public function htmlParseFilter($html): string
	{
		if (empty($html)) return '';
		$html = preg_replace_callback('/(src=\\")(.*?)(\\")/', function ($matches) {
			return $matches[1] . $this->images->storeImage($matches[2]) . $matches[3];
		}, $html);
		return $html;
	}

	/**
	 * Estimate reading time required for a markup text.
	 *
	 * @param Entry $entry
	 * @return string
	 */
	public function readTimeFilter(Entry $entry): string
	{
		$content = $entry->blogContent->all();
		$content = array_filter($content, function ($element) {
			return $element->text != null;
		});
		$content = array_map(function ($text) {
			return $text->text->getParsedContent();
		}, $content);
		$content = implode(' ', $content);
		$word = str_word_count(strip_tags($content));
		$est = round($word / 200);
		$readingTime = Craft::t('core', 'minutes of reading time');
		return "$est $readingTime";
	}

	/**
	 * Twig abstraction for array_splice.
	 *
	 * @param array $array
	 * @param integer $offset
	 * @param int $length
	 * @param array $replacement
	 * @return array
	 */
	public function spliceFilter(array $array, int $offset, $length = null, $replacement = [])
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
}
