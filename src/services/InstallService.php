<?php

namespace Developion\Core\services;

use craft\base\Component;
use craft\services\Globals;

class InstallService extends Component
{
	public function __construct(
		protected Globals $globals
	) {
		parent::__construct();
	}
}
