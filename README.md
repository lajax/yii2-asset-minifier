Yii2 AssetMinifier
=================
Runtime minification and combination of asset files.

Installation
------------

Extension for runtime minification and combination of asset files (css, js)

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist lajax/yii2-asset-minifier "*"
```

or add

```
"lajax/yii2-asset-minifier": "*"
```

to the require section of your `composer.json` file.


Usage
-----

##Config

###Minimal Configuration

```php
'bootstrap' => ['assetMinifier'],
'components' => [
    // ...
    'assetMinifier' => [
        'class' => \lajax\assetminifier\Component::className(),
    ],
    // ...
],
// ...
```

###Full Configuration

```php
'bootstrap' => ['assetMinifier'],
'components' => [
    // ...
    'assetMinifier' => [
        'class' => \lajax\assetminifier\Component::className(),
        'minifyJs' => true,                     // minify js files. [default]
        'minifyCss' => true,                    // minify css files [default]
        'combine' => true,                      // combine asset files. [default]
        'createGz' => false,                    // create compressed .gz file, (so the web server doesn’t need to
                                                // compress asset files on each page view). Requires
                                                // special web server configuration. [default]
        'minifier' => [                         // Settings of the components performing the minification of asset files
            'workPath' => lajax\assetminifier\Minifier::WORKPATH_SOURCE, // default setting
            'js' => '', // override default minifier, see available minifiers below
            'css' => '', // override default minifier, see available minifiers below
        ],
        'combiner' => [
            'class' => 'lajax\assetminifier\Combiner',
            'combinedFilesPath' => '/lajax-asset-minifier'      // default setting
        ]
    ],
    // ...
]
// ...
```

####AVAILABLE MINIFIERS:

* #WEB:#

```php
'js' => [                           // minify js via web API
    'class' => 'lajax\assetminifier\minifiers\WebJsMinifier',
    'url' => 'http://javascript-inifier.com/raw'   // default setting
],
'css' => [
    'class' => 'lajax\assetminifier\minifiers\WebCssMinifier',
    'url' => 'http://cssminifier.com/raw'           // default setting
]
```

* #PHP (*Default minifiers*):#

```php
'js' => [                                        // Default JS minifier.
    'class' => 'lajax\assetminifier\minifiers\PhpJsMinifier',
    // default settings, you can override them
    'options' => [
       'flaggedComments' => true                // Disable YUI style comment preservation.
    ]
],
'css' => [                                       // Default CSS minifier.
    'class' => 'lajax\assetminifier\minifiers\PhpCssMinifier',
    // default settings, you can override them
    'filters' => [
        'ImportImports' => false,
        'RemoveComments' => true,
        'RemoveEmptyRulesets' => true,
        'RemoveEmptyAtBlocks' => true,
        'ConvertLevel3AtKeyframes' => false,
        'ConvertLevel3Properties' => false,
        'Variables' => true,
        'RemoveLastDelarationSemiColon' => true
    ],
    'plugins' => [
        'Variables' => true,
        'ConvertFontWeight' => true,
        'ConvertHslColors' => true,
        'ConvertRgbColors' => true,
        'ConvertNamedColors' => true,
        'CompressColorValues' => true,
        'CompressExpressionValues' => true,
    ]
]
```

* #CLI:#

```php
'js' => [
    'class' => 'lajax\assetminifier\minifiers\CliJsMinifier',
    // default settings, you can override them
    'command' => 'java -jar ' . Yii::getAlias('@vendor/packagist/closurecompiler-bin/bin/compiler.jar') . ' --js {from}',
],
'css' => [
    'class' => 'lajax\assetminifier\minifiers\CliCssMinifier',
    // default settings, you can override them
    'command' => 'java -jar ' . Yii::getAlias('@vendor/packagist/yuicompressor-bin/bin/yuicompressor.jar') . ' --type css {from}',
]
```



##Minifiers

###JavaScript minifiers:

* Web: [javascript-minifier.com](http://javascript-minifier.com/)
* Php: [CssMin](https://github.com/natxet/CssMin)
* Cli: [compiler](https://github.com/packagist/closurecompiler-bin)

###StyleSheet minifiers:

* Web: [cssminifier.com](http://cssminifier.com/)
* Php: [JShrink](http://blog.tedivm.com/category/projects/jshrink/)
* Cli: [Yui Compressor](https://github.com/packagist/yuicompressor-bin)


###Serving *.js.gz and *.css.gz files instead of *.js or *.css in Nginx:

```
gzip_static on | of | always
```

[Nginx gzip static module](http://nginx.org/en/docs/http/ngx_http_gzip_static_module.html)

Links
-----

- [GitHub](https://github.com/lajax/yii2-asset-minifier)
- [Api Docs](http://lajax.github.io/yii2-asset-minifier)
- [Packagist](https://packagist.org/packages/lajax/yii2-asset-minifier)
- [Yii Extensions](http://www.yiiframework.com/extension/yii2-asset-minifier)