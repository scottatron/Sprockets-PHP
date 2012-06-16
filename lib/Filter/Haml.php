<?php
namespace Filter;

class Haml extends Base
{
	private $parser;
	
	//lazy instanciation
	// (not instanciated if a cache file is found)
	private function getParser()
	{
		if (!$this->parser)
			$this->parser = new \HamlParser();
		
		return $this->parser;
	}

	public function __invoke($content, $file, $vars)
	{
		if (!file_exists($path = $this->getCacheDir() . $file))
			file_put_contents($path, $this->getParser()->parseText($content));
	
		ob_start();
		include $path;
		return ob_get_clean();
	}
}