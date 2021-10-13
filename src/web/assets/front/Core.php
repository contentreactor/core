<?php

namespace Developion\Core\web\assets\front;

use craft\web\AssetBundle;

class Core extends AssetBundle
{
    public function init()
    {
        $this->sourcePath = __DIR__ . '/dist';

        $this->depends = [];

        $this->js = [
            'app.js'
        ];

        $this->css = [
            [
                'app.css',
                'as' => 'style',
                'rel' => 'stylesheet preload'
            ],
        ];

        parent::init();
    }
}
