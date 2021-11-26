<?php

namespace Developion\Core\services;

use Craft;
use craft\base\ApplicationTrait;
use craft\base\Component;
use craft\base\VolumeInterface;
use craft\elements\Asset;
use craft\helpers\FileHelper;
use craft\helpers\Session;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;
use craft\image\Raster;
use craft\models\VolumeFolder;
use craft\services\AssetIndexer;
use craft\volumes\Local;
use Developion\Cache\Plugin as CachePlugin;
use Yii;

class ImagesService extends Component
{
	/** @var VolumeFolder $folder */
	protected $folder;

	public $renderPath = 'api-render';

	public $importedPath = 'imported';

	public $temporaryPath = 'tmp';

	private function createDirs()
	{
		if (Craft::$app->getPlugins()->isPluginEnabled('developion-cache')) {
			$this->renderPath		= CachePlugin::$plugin->getSettings()->renderPath['value'];
			$this->importedPath		= CachePlugin::$plugin->getSettings()->volumePath['value'];
			$this->temporaryPath	= CachePlugin::$plugin->getSettings()->temporaryPath['value'];
		}

		$tmpDir = sprintf('%s/%s', Craft::$app->getPath()->getStoragePath(), $this->temporaryPath);
		if (!is_dir($tmpDir)) {
			FileHelper::createDirectory($tmpDir);
		}

		if (!is_dir(Yii::getAlias("@webroot/{$this->getVolume()->path}/{$this->importedPath}"))) {
			FileHelper::createDirectory(Yii::getAlias("{$this->getVolume()->path}/{$this->importedPath}"));
			/** @var AssetIndexer $assetIndexerService */
			$assetIndexerService = Craft::$app->getAssetIndexer();
			$sessionId = $assetIndexerService->getIndexingSessionId();
			$volumeIds = Craft::$app->getVolumes()->getViewableVolumeIds();

			$missingFolders = [];
			$skippedFiles = [];

			foreach ($volumeIds as $volumeId) {
				$indexList = $assetIndexerService->prepareIndexList($sessionId, $volumeId);

				if (!empty($indexList['error'])) {
					return false;
				}

				if (isset($indexList['missingFolders'])) {
					$missingFolders += $indexList['missingFolders'];
				}

				if (isset($indexList['skippedFiles'])) {
					$skippedFiles = $indexList['skippedFiles'];
				}

				$response['volumes'][] = [
					'volumeId' => $volumeId,
					'total' => $indexList['total'],
				];
			}

			Session::set('assetsVolumesBeingIndexed', $volumeIds);
			Session::set('assetsMissingFolders', $missingFolders);
			Session::set('assetsSkippedFiles', $skippedFiles);
		}

		if (!is_dir(Yii::getAlias("@webroot/{$this->renderPath}"))) {
			FileHelper::createDirectory(Yii::getAlias("@webroot/" . $this->renderPath));
		}

		if (!$this->folder) {
			$this->folder = Craft::$app->getAssets()
				->findFolder([
					'name' => $this->importedPath
				]);
		}
	}

	/**
	 * Renders URL of the passed CMS Asset or Image URL. Returns original URL if it’s not valid.
	 *
	 * @param Asset|string $asset
	 * @param array $config
	 * @param boolean $returnAsset
	 * @return Asset|string|bool
	 */
	public function storeImage($asset, array $config = [], $returnAsset = false)
	{
		if ('string' == gettype($asset)) {
			$assetUpload = $this->storeImageExternal($asset);
			if (!$assetUpload) {
				return $asset;
			}
			$asset = $assetUpload;
		}
		$config = array_merge([
			'resx' => 0,
			'extension' => 'webp',
			'append' => '',
			'horizontalCrop' => false
		], $config);
		extract($config);
		if (!in_array($asset->getMimeType(), [
			'image/png',
			'image/jpeg',
			'image/webp'
		])) {
			return $returnAsset ? $asset : $asset->getUrl();
		}
		/** @var Asset $asset */
		$volumePath = $asset->getVolume()->settings['path'];
		$folderPath = $asset->getFolder()->path;
		$rawName = substr($asset->filename, 0, strrpos($asset->filename, '.'));
		$output_file = Yii::getAlias("@webroot/" . $this->renderPath . "/$rawName$append.$extension");
		$image = new Raster();
		$image->loadImage(Yii::getAlias("$volumePath/$folderPath{$asset->filename}"));

		if (file_exists($output_file)) {
			list($width, $height) = getimagesize($output_file);
			if ($resx === 0 || $resx === $width || (is_array($resx) && $resx['width'] === $width)) {
				return $returnAsset ? $asset : Yii::getAlias('@web') . "/" . $this->renderPath . "/$rawName$append.$extension";
			}
		}
		if (is_array($resx)) {
			if ($horizontalCrop) {
				$image->scaleAndCrop($asset->width * $resx['height'] / $resx['width'], $asset->height, true, $asset->getFocalPoint());
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
					$x1 = $focal['x'] * $asset->width - $resx['width'] / 2;
					$x2 = $focal['x'] * $asset->width + $resx['width'] / 2;
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
					$y1 = $focal['y'] * $asset->height - $resx['height'] / 2;
					$y2 = $focal['y'] * $asset->height + $resx['height'] / 2;
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
			return $returnAsset ? $asset : Yii::getAlias('@web') . "/" . $this->renderPath . "/$rawName$append.$extension";
		}
		return $returnAsset ? $asset : $asset->getUrl();
	}

	/**
	 * Generates CMS Asset from given URL. Returns false if URL isn’t valid.
	 *
	 * @param string $url
	 * @return Asset|bool
	 */
	protected function storeImageExternal(string $url,)
	{
		$this->createDirs();

		if (!$this->readTest($url)) return false;

		$fileInfo = pathinfo($url);
		$tmpPath = sprintf('%s/%s/%s', Craft::$app->getPath()->getStoragePath(), $this->temporaryPath, $fileInfo['basename']);
		file_put_contents($tmpPath, file_get_contents($url));

		/** @var Asset $asset */
		$asset = Asset::find()
			->where(['filename' => StringHelper::toSnakeCase(urldecode($fileInfo['basename']))])
			->one();

		if ($asset) {
			if ($this->readTest(UrlHelper::siteUrl($asset->getUrl()))) return $asset;
			else Craft::$app->elements->deleteElement($asset);
		}

		$asset = new Asset();
		$asset->tempFilePath = $tmpPath;
		$asset->title = StringHelper::titleizeForHumans(urldecode($fileInfo['basename']));
		$asset->filename = StringHelper::toSnakeCase(urldecode($fileInfo['basename']));
		$asset->newFolderId = $this->folder->id;
		$asset->volumeId = $this->folder->volumeId;
		$asset->avoidFilenameConflicts = false;
		$asset->setScenario(Asset::SCENARIO_CREATE);

		$result = Craft::$app->getElements()->saveElement($asset, false);

		if ($result) {
			return $asset;
		}
		return false;
	}

	/**
	 * Test for redirects, availability and validity of image URL.
	 *
	 * @param string $url
	 * @return void
	 */
	public function readTest($url)
	{
		if (@get_headers($url)[0] == 'HTTP/1.1 302 Found') {
			$url = StringHelper::slice(@get_headers($url)[7], strlen('Location: '));
		}

		if (@get_headers($url)[0] !== 'HTTP/1.1 200 OK') {
			return false;
		}
		try {
			getimagesize($url);
		} catch (\Throwable $th) {
			return false;
		}

		return true;
	}

	private function getVolume(): VolumeInterface
	{
		$assetVolumes = Craft::$app->getVolumes()->getAllVolumes();
		/** @var Local */
		$defaultVolume = array_shift($assetVolumes);
		if (!$defaultVolume) {
			$defaultVolume = new Local([
				'name' => 'Blog Images',
				'handle' => 'blogImages',
				'hasUrls' => true,
				'titleTranslationMethod' => 'site',
				'titleTranslationKeyFormat' => null,
				'url' => '@web/blogImages',
				'path' => '@webroot/blogImages',
			]);
			Craft::$app->getVolumes()->saveVolume($defaultVolume);
		}

		return $defaultVolume;
	}
}
