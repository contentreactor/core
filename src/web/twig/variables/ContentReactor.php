<?php

namespace ContentReactor\Core\web\twig\variables;

use craft\helpers\UrlHelper;

class ContentReactor
{
	public static function baseUrl($path): string
	{
		$baseUrl = UrlHelper::hostInfo(UrlHelper::siteUrl($path));

		return rtrim($baseUrl, '/') . '/' . ltrim($path, '/');
	}
}
