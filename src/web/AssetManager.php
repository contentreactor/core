<?php

namespace Developion\Core\web;

use Craft;
use craft\helpers\FileHelper;
use craft\web\AssetManager as WebAssetManager;

class AssetManager extends WebAssetManager
{
    /** @inheritDoc */
    public function getPublishedUrl($sourcePath, bool $publish = false, $filePath = null)
    {
        if ($publish === true) {
            [, $url] = $this->publish($sourcePath);
        } else {
            $url = parent::getPublishedUrl($sourcePath);
        }

        if ($filePath !== null) {
            $url .= '/' . $filePath;
        }

        return $url;
    }
}
