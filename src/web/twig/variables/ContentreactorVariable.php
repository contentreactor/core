<?php

namespace ContentReactor\Core\web\twig\variables;

use craft\helpers\Html;
use ContentReactor\Core\Core;
use yii\di\ServiceLocator;
use yii\widgets\ActiveForm;

class ContentReactorVariable extends ServiceLocator
{
	public function __construct($config = [])
	{
		$components = [
			'form' => ActiveForm::class,
			'html' => Html::class,
		];

		$config['components'] = $components;

		parent::__construct($config);
	}

	public function getPluginName()
	{
		return Core::getInstance()->name;
	}
}
