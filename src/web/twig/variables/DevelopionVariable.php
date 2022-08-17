<?php

namespace Contentreactor\Core\web\twig\variables;

use craft\helpers\Html;
use Contentreactor\Core\Core;
use yii\di\ServiceLocator;
use yii\widgets\ActiveForm;

class ContentreactorVariable extends ServiceLocator
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
