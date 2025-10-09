<?php
declare(strict_types=1);

namespace ContentReactor\Core\Web\Twig\Variables;

use craft\helpers\UrlHelper;

class ContentReactor
{
	public static function baseUrl($path): string
	{
		$baseUrl = UrlHelper::hostInfo(UrlHelper::siteUrl($path));

		return rtrim($baseUrl, '/') . '/' . ltrim($path, '/');
	}
}
