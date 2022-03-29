<?php

namespace Developion\Core\web\twig\variables;

use craft\helpers\Html;
use Developion\Core\Core;
use yii\di\ServiceLocator;
use yii\widgets\ActiveForm;

/**
 * Class DevelopionVariable
 */
class DevelopionVariable extends ServiceLocator
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

	public function getSettings()
	{
		
	}
}
