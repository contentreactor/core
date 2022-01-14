<?php

namespace Developion\Core\web\twig\variables;

use craft\helpers\Html;
use yii\di\ServiceLocator;
use yii\widgets\ActiveForm;

class DevelopionVariable extends ServiceLocator
{
	public function __construct($config = [])
	{
		$components = [
			'form' => ActiveForm::class,
			'html' => Html::class
		];

        $config['components'] = $components;

		parent::__construct($config);
	}
}
