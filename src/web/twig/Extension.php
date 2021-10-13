<?php

namespace Developion\Core\web\twig;

use Craft;
use craft\elements\Entry;
use craft\elements\Asset;
use craft\helpers\ArrayHelper;
use craft\helpers\UrlHelper;
use craft\image\Raster;
use craft\web\View;
use Developion\Core\web\AssetManager;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Yii;

class Extension extends AbstractExtension implements GlobalsInterface
{
    /**
     * Return our Twig functions
     *
     * @return array
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('baseUrl', [UrlHelper::class, 'rootRelativeUrl']),
            new TwigFunction('storeImage', [$this, 'storeImage']),
            new TwigFunction('storeImageExternal', [$this, 'storeImageExternal']),
            new TwigFunction('field', [$this, 'renderFormMacro'], ['is_safe' => ['html']]),
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
            new TwigFilter('sluggifyCamel', [$this, 'sluggifyCamel']),
            new TwigFilter('readTime', [$this, 'readTime'])
        ];
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

    public function getGlobals()
    {
        $minified = (object)[
            'segment' => Craft::$app->request->getSegment(1),
            'segments' => Craft::$app->request->getSegments(),
            'segmentjs' => file_exists(CRAFT_BASE_PATH . '/web/assets/' . Craft::$app->request->getSegment(1) . '.js'),
            'segmentcss' => file_exists(CRAFT_BASE_PATH . '/web/assets/' . Craft::$app->request->getSegment(1) . '.css'),
            'css' => file_exists(CRAFT_BASE_PATH . '/web/assets/app.css') ? file_get_contents(CRAFT_BASE_PATH . '/web/assets/app.css') : '',
        ];
        
        $assetManager = new AssetManager();
        
        return [
            'minified' => $minified,
            'assetManager' => $assetManager
        ];
    }

    public function sluggifyCamel(string $camel)
    {
        return strtolower(preg_replace('/(?<=\d)(?=[A-Za-z])|(?<=[A-Za-z])(?=\d)|(?<=[a-z])(?=[A-Z])/', '-', $camel));
    }

    public function readTime(Entry $entry): string
    {
        $content = $entry->blogContent->all();
        $content = array_filter($content, function($element) {
            return $element->text != null;
        });
        $content = array_map(function($text) {
            return $text->text->getParsedContent();
        }, $content);
        $content = implode(' ', $content);
        $word = str_word_count(strip_tags($content));
        $est = round($word / 200);
        $readingTime = Craft::t('core', 'minutes of reading time');
        return "$est $readingTime";
    }
    
    public function storeImage(Asset $asset, array $config = [])
    {
        $config = array_merge([
            'resx' => 0,
            'extension' => 'webp',
            'append' => '',
            'horizontalCrop' => false
        ], $config);
        extract($config);
        if ($asset->getExtension() === 'svg') {
            return $asset->getUrl();
        }
        $volumePath = $asset->getVolume()->settings['path'];
        $folderPath = $asset->getFolder()->path;
        $rawName = substr($asset->filename, 0, strrpos($asset->filename, '.'));
        $assetFilePath = Yii::getAlias($volumePath) . "/$folderPath{$asset->filename}";
        $output_file = CRAFT_BASE_PATH . "/web/api-render/$rawName$append.$extension";
        $image = new Raster();
        $image->loadImage($assetFilePath);

        if (file_exists($output_file)) {
            list($width, $height) = getimagesize($output_file);
            if ($resx === 0 || $resx === $width || (is_array($resx) && $resx['width'] === $width)) return getenv('PRIMARY_SITE_URL') . "/api-render/$rawName$append.$extension";
        }
        if (is_array($resx)) {
            if ($horizontalCrop) {
                $image->scaleAndCrop($asset->width*$resx['height']/$resx['width'], $asset->height, true, $asset->getFocalPoint());
            } else {
                $focal = $asset->getFocalPoint();
                $full_width = $full_height = false;
                if ($resx['width'] >= $asset->width) $full_width = true;
                if ($resx['height'] >= $asset->height) $full_height = true;
                $x1 = $x2 = $y1 = $y2 = 0;
                if ($full_width) {
                    $x1 = 0;
                    $x2 = $asset->width;
                } else {
                    $x1 = $focal['x']*$asset->width - $resx['width']/2;
                    $x2 = $focal['x']*$asset->width + $resx['width']/2;
                    if ($x1 < 0) {
                        $xoffset = 0 - $x1;
                        $x1 = 0;
                        $x2 = $x2 + $xoffset;
                    }
                    if ($x2 > $asset->width) {
                        $xoffset = $x2 - $asset->width;
                        $x2 = $asset->width;
                        $x1 = $x1 - $xoffset;
                    }
                }
                if ($full_height) {
                    $y1 = 0;
                    $y2 = $asset->height;
                } else {
                    $y1 = $focal['y']*$asset->height - $resx['height']/2;
                    $y2 = $focal['y']*$asset->height + $resx['height']/2;
                    if ($y1 < 0) {
                        $yoffset = 0 - $y1;
                        $y1 = 0;
                        $y2 = $y2 + $yoffset;
                    }
                    if ($y2 > $asset->height) {
                        $yoffset = $y2 - $asset->height;
                        $y2 = $asset->height;
                        $y1 = $y1 - $yoffset;
                    }
                }
                $image->crop($x1, $x2, $y1, $y2);
            }
        } else {
            if ($resx !== 0) {
                $image->scaleToFit($resx);
            }
        }

        if ($image->saveAs($output_file)) {
            return getenv('PRIMARY_SITE_URL') . "/api-render/$rawName$append.$extension";
        }
        return $asset->getUrl();
    }

    public function storeImageExternal(string $url, array $config = [])
    {
        $fileInfo = pathinfo($url);
		if ($fileInfo['extension'] === 'svg') return $url;
		$tmpPath = CRAFT_BASE_PATH . '/tmp/' . $fileInfo['basename'];
		file_put_contents($tmpPath, file_get_contents($url));
		
        $assets = Craft::$app->getAssets();
        /** @var \craft\models\VolumeFolder $folder */
        $folder = $assets->findFolder(['name' => 'Main Volume']);
        
        $asset = Asset::find()
            ->where(['filename' => $fileInfo['basename']])
            ->one();
        if ($asset != null) {
            return $this->storeImage($asset, $config);
        }
        
        $asset = new Asset();
        $asset->tempFilePath = $tmpPath;
        $asset->filename = $fileInfo['basename'];
        $asset->newFolderId = $folder->id;
        $asset->volumeId = $folder->volumeId;
        $asset->avoidFilenameConflicts = false;
        $asset->setScenario(Asset::SCENARIO_CREATE);
    
        $result = Craft::$app->getElements()->saveElement($asset);
        
        if ($result) {
            return $this->storeImage($asset, $config);
        }
        return $url;
	}
    
    public function renderFormMacro(string $fieldType, array $fieldOptions) : string
    {
        $oldMode = Craft::$app->view->getTemplateMode();
        Craft::$app->view->setTemplateMode(View::TEMPLATE_MODE_CP);
        $html = Craft::$app->view->renderTemplateMacro('_includes/forms', $fieldType, [$fieldOptions]);
        Craft::$app->view->setTemplateMode($oldMode);
        
        return $html;
    }
}
