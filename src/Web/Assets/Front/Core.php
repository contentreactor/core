<?php
declare(strict_types=1);

namespace ContentReactor\Core\Web\Assets\Front;

use craft\web\AssetBundle;

class Core extends AssetBundle
{
	public function init(): void
	{
		$this->sourcePath = __DIR__ . '/dist';

		$this->depends = [];

		$this->js = [
			'app.js',
		];

		$this->css = [
			[
				'app.css',
				'as' => 'style',
				'rel' => 'stylesheet preload',
			],
		];

		parent::init();
	}
}
