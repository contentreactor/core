<?php

namespace Contentreactor\Core\web\assets\cp;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;
use verbb\base\assetbundles\CpAsset as VerbbCpAsset;
use verbb\base\BaseHelper;

class Core extends AssetBundle
{
	public function init()
	{
		BaseHelper::registerModule();

		$this->depends = [
			VerbbCpAsset::class,
			CpAsset::class,
		];

		parent::init();
	}
}
