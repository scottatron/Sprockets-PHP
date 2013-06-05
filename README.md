Sprockets-PHP
===============

This is a port of Sprockets (Rails Asset Pipeline) for PHP.

You have to create an instance of `Asset\Pipeline`.
The argument is the array of "base paths" from where the Pipeline has to search files.

If you want to call directly the Pipeline, you can then do `$pipeline($asset_type)`.
For example `$pipeline('css');`.
The CMS will load `application.css` in one of the base paths.
This file must contain "directives", like Sprockets's one.

```php
// require your autoloader
...

// read paths.json - see below
$paths = str_replace('%template%', 'MyTemplate', file_get_contents('paths.json'));
$paths = json_decode($paths, true);

// create a pipeline with 2 directories
$pipeline = new Asset\Pipeline($paths);

// finds `application.css` in the paths
echo $pipeline('css');

// uses `layout.css`
echo $pipeline('css', 'layout');

// same as the first example, but will cache it into a file
$cache = new \Asset\Cache($pipeline, 'css', $vars = array(), $options = array());
// $options you can pass :
// `minify` whether you want to minify the output or not
// - `.js` : Minified through [Esmangle](https://github.com/Constellation/esmangle)
// - `.css` : Minified through [Clean-CSS](https://github.com/GoalSmashers/clean-css)
$content = $cache->getContent();
$filename = (string) $cache;
//or
$filename = $cache->getFilename();
```

## Asset Paths

The asset paths are divided by "modules", allowing you for the greatest modularity :

```json
{
  "template": {
    "directories": [
      "app/themes/%template%/assets/",
      "app/themes/_shared/assets/",
      "lib/assets/",
      "vendor/assets/"
    ],
    "prefixes": {
      "js": "javascripts",
      "css": "stylesheets",
      "img": "images",
      "font": "fonts"
    }
  },
  "external": {
    "directories": [
      "vendor/bower/",
      "vendor/components/"
    ]
  }
}
```

You have 2 keys in each modules : the `directories`, which list directories where the Pipeline must search files, and `prefixes`, which will append the path for the extension to the directory (ie a `js` file will get `javascripts/` appended to its paths).

For example, if we run `$pipeline('js')`, the pipeline will try to find the following files :
 - `app/themes/%template%/assets/javascripts/application.js` (`%template%` being replaced in the example above)
 - `app/themes/_shared/assets/javascripts/application.js`
 - `lib/assets/javascripts/application.js`
 - `vendor/assets/javascripts/application.js`

 - `vendor/bower/application.js`
 - `vendor/components/application.js`

This example file, allowing to use a Rails-like `javascripts/` directory for js file gracefully, also supports `//= require jquery/jquery` to find `vendor/bower/jquery/jquery.js`

Only the "meaningful" extension matters (using a whitelist).
```
/**
 * for example
 *= require datatables/js/jquery.dataTables
 * will find correctly the file named
 * "vendor/bower/datatables/js/jquery.dataTables.js.coffee"
 * and the "coffee" filter will be correctly applied.
 */
```

## Cache
All files are, by default, cached in `cache/assets`.
You can change `Pipeline::$cacheDirectory` (by default `cache/`, `assets/` is automatically appended)

### Node path
You can change the `node` executable path by `define()`ing it.
```php
// may be :
//unix
// * /usr/bin/node
// * /usr/local/bin/node
//windows
// * "C:/Program Files (x86)/nodejs/node"
// * "C:/Program Files/nodejs/node"
//it may vary however
define('NODE_BINARY', 'node');
// if you want to use your own npm modules and **override** the Pipeline's one.
define('NODE_MODULES_PATH', '/my/node_modules/');
```
You shouldn't need to change it if it's in your path, however.

## Directives Syntax
There are three supported syntaxs at this moment.

```php
//= only for js
#= only for js
/**
 *= for any
 */
```

## Supported Directives
The directives disponibles are : `require`, `require_directory`, `require_tree` and `depends_on`

### require
Requires a file directly, from the relative path OR one of the base path.
You can also give a directory name, if this directory has a file named "index.$type" (here, "index.css") in.

### require_directory
Requires each file of the directory. Not recursive.

### require_tree
Recursively requires each file of the directory tree.

### depends_on
Adds the file to the dependencies, even if the file isn't included.
For example, in application.css

```php
//= depends_on image.png
//= depends_on layout
```

If this file change, the whole stylesheet (and the dependencies) will be recompiled
 (this is meant for inlining of some preprocessors).

## Filters
The available filters are :

Languages :
 - .php : [PHP](http://php.net)

JavaScript :
 - .ls : [LiveScript](http://livescript.org)
 - .coffee : [CoffeeScript](http://coffeescript.org) (through [coffeeScript-php](github.com/alxlit/coffeescript-php)

Stylesheet :
 - .styl : [Stylus](http://learnboost.github.io/stylus/)
 - .sass .scss : [Sass](http://sass-lang.com/) (through [PHPSass](http://phpsass.com/))
 - .less : [Less](http://lesscss.org) (through [lessphp](http://leafo.net/lessphp/))

Html :
 - .haml : [Haml](http://haml.info) (through [MtHaml](https://github.com/arnaud-lb/MtHaml/), upon which you can build a Twig version, for example)


Adding filter is very easy (to create a `.twig` filter or a `.md`, for example). Just add it to the pipeline :
```
$pipeline->registerFilter('md', 'My\Markdown\Parser');
```

You must implement an interface like `\Asset\Filter\Interface` :
```
interface Interface
{
	/**
	 * @return string processed $content
	 */
	public function __invoke($content, $file, $dir, $vars);
}
```

You can also inherit `Asset\Filter\Base` which gives you access to :
 - `$this->pipeline` current pipeline instance
 - `$this->processNode()` passing an argument array, auto-quoted, like this : `array('modulename/bin/mod', '-c', $file))`
   Note that the first argument gets the `NODE_MODULES_PATH` prepended automatically.