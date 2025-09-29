<?php
declare(strict_types=1);

it('verifies the plugin is an instance of plugin', function () {
	expect(\ContentReactor\Core\Core::class)->toExtend(\craft\base\Plugin::class);
});