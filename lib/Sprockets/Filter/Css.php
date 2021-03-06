<?php
namespace Sprockets\Filter;

/**
 * Fix `url()`s
 */
class Css extends Base
{
	const URL_REGEX = '`url\([\'"]?([a-zA-Z0-9/\._-]+)[\'"]?\)`';
	public function __invoke($content, $file, $dir, $vars)
	{
		$base_url = str_repeat('../', substr_count($this->pipeline->getOption('CACHE_DIRECTORY'), '/'));

		return preg_replace_callback(self::URL_REGEX, function ($match) use ($dir, $base_url)
		{
			$file = new \Asset\File(($dir ? $dir . '/' : '') . $match[1]);
			//XXX maybe we should actually cache the image?
			return 'url(' . $base_url . $file->getFilepath() . ')';
		}, $content);
	}
}