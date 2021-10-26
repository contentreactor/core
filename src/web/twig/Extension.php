<?php

namespace Developion\Core\web\twig;

use Craft;
use craft\elements\Asset;
use craft\elements\Entry;
use craft\helpers\UrlHelper;
use Developion\Core\Core;
use Developion\Core\services\ImagesService;
use GuzzleHttp\Client;
use Symfony\Component\VarDumper\VarDumper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class Extension extends AbstractExtension
{
    /** @var ImagesService $images */
    protected $images;

    public function __construct()
    {
        $this->images = Core::$plugin->images;
    }
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
            new TwigFunction('image', [$this, 'imageFunction']),
            new TwigFunction('fetch', [$this, 'fetchFunction']),
            new TwigFunction('dd', [$this, 'ddFunction']),
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
            new TwigFilter('readTime', [$this, 'readTimeFilter']),
            new TwigFilter('splice', [$this, 'spliceFilter'])
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
            'parseJson' => true,
            'method' => 'GET',
            'options' => [],
            'duration' => 60 * 60 * 24,
            'key' => ''
        ], $config);
        extract($config);

        $client = new Client([
            'base_uri' => $baseUrl,
            'timeout' => 10
        ]);

        $hash = Craft::$app->security->hashData($endpoint, null);
        $key = $key ?: $hash;
        $error = false;
        if (Craft::$app->cache->exists($key)) {
            return ['body' => Craft::$app->cache->get($key)];
        }
        
        try {
            $response = $client->request($method, $endpoint, $options);
            if ($parseJson) {
                $body = json_decode($response->getBody(), true);
            } else {
                $body = (string)$response->getBody();
            }
            $body = Craft::$app->cache->add($key, $body, $duration);
            $statusCode = $response->getStatusCode();
            $reason = $response->getReasonPhrase();
        } catch (\Exception $e) {
            $error = true;
            $reason = $e->getMessage();
        }
        return [
            'reason' => $reason,
            'status' => $error ?? $statusCode,
            'body' => $body
        ];
    }

    /**
     * Renders responsive image template.
     *
     * @param Asset|string $image
     * @param array $config
     * @param Asset|string|null $imageMobile
     * @param string $alt
     * @return string
     */
    public function imageFunction(Asset|string $image, $config = [], Asset|string $imageMobile = null, string $alt = ''): string
    {
        $thumbs = $mobile = [];
        $config = array_merge([
            'template' => 'developion-core/components/image',
            'params' => []
        ], $config);
        extract($config);
        $imageAsset = $this->images->storeImage($image, [], true);
        foreach ($params as $paramKey => $param) {
            $thumbs['webp'][$paramKey] = $this->images->storeImage($imageAsset, $param['params']);
            $thumbs['jpeg'][$paramKey] = $this->images->storeImage($imageAsset, array_merge($param['params'], ['extension' => 'jpg']));
        }
        if ($imageMobile && !empty($params)) {
            $imageAssetMobile = $this->images->storeImage($imageMobile, [], true);
            $mobile['webp'] = $this->images->storeImage($imageAssetMobile, $params[0]['params']);
            $mobile['jpeg'] = $this->images->storeImage($imageAssetMobile, array_merge($params[0]['params'], ['extension' => 'jpg']));
        }

        $html = Craft::$app->getView()->renderTemplate($template, [
            'params' => $params,
            'thumbs' => $thumbs,
            'mobile' => $mobile,
            'alt' => $alt ?: $imageAsset->title,
        ]);

        return $html;
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
}
