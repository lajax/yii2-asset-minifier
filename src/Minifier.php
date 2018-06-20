<?php

namespace lajax\assetminifier;

use Yii;
use yii\helpers\Url;
use yii\helpers\FileHelper;
use lajax\assetminifier\helpers\AssetMinifier;

/**
 * Class performing the minification of asset files.
 * 
 * @author Lajos Molnár <lajax.m@gmail.com>
 * @since 1.0
 */
class Minifier extends \yii\base\BaseObject
{

    /**
     * Processed files are same as source files.
     * Recommended in case of css and js files. Effectively replaces forceCopy.
     */
    const WORKPATH_SOURCE = 'sourcePath';

    /**
     * The processed files are located in web/assets/* 
     * Recommended for runtime conversion of less, scss, sass, styl, coffee, ts.
     */
    const WORKPATH_BASE = 'basePath';

    /**
     * @var minifiers\MinifierInterface Object performing minification of JavaScript files.
     */
    public $js;

    /**
     * @var minifiers\MinifierInterface Object performing minification StyleSheet files.
     */
    public $css;

    /**
     * @var string Type of processed asset file (basePath|sourcePath).
     * 
     * ~~~
     * lajax\assetminifier\Minifier::WORKPATH_SOURCE    // If we only use js anc css.
     * lajax\assetminifier\Minifier::WORKPATH_BASE      // If we convert css or js files during runtime.
     * ~~~
     * 
     */
    public $workPath = self::WORKPATH_SOURCE;

    /**
     * @var boolean Creating corresponding .gz files for asset files.
     */
    public $createGz = false;

    /**
     * @var boolean whether the directory being published should be copied even if
     * it is found in the target directory. This option is used only when publishing a directory.
     * You may want to set this to be `true` during the development stage to make sure the published
     * directory is always up-to-date. Do not set this to true on production servers as it will
     * significantly degrade the performance.
     */
    public $forceCopy;

    /**
     * @var string
     */
    private $_webroot;

    /**
     * @var string
     */
    private $_web;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        if ($this->forceCopy === null) {
            $this->forceCopy = Yii::$app->assetManager->forceCopy;
        }

        $this->_webroot = Yii::getAlias('@webroot/assets');
        $this->_web = Yii::getAlias('@web/assets');
    }

    /**
     * Minification of js files of the assetBundle received as a parameter.
     * @param \yii\web\AssetBundle $assetBundle
     * @return \yii\web\AssetBundle
     */
    public function minifyJs($assetBundle)
    {
        return $this->minifyAssetBundleFiles($assetBundle, 'js');
    }

    /**
     * Minification of js files of the assetBundle received as a parameter.
     * @param \yii\web\AssetBundle $assetBundle
     * @return \yii\web\AssetBundle
     */
    public function minifyCss($assetBundle)
    {
        return $this->minifyAssetBundleFiles($assetBundle, 'css');
    }

    /**
     *
     * @param type $assetBundle
     * @param string $fileType css|js
     */
    protected function minifyAssetBundleFiles($assetBundle, $fileType)
    {
        foreach ($assetBundle->{$fileType} as $key => $options) {
            $hasOptions = is_array($options);
            $file = $hasOptions ? array_shift($options) : $options;
            $minFile = $this->minify($assetBundle, $file);

            if ($hasOptions) {
                array_unshift($options, $minFile);
            } else {
                $options = $minFile;
            }

            $assetBundle->{$fileType}[$key] = $options;
        }

        return $assetBundle;
    }

    /**
     * Function performing the minification of asset files.
     * @param \yii\web\AssetBundle $bundle
     * @param string $filename
     * @return string The path of the minified file.
     */
    protected function minify($bundle, $filename)
    {
        if (Url::isRelative($filename)) {
            if ($this->workPath === self::WORKPATH_SOURCE) {
                $sourcePath = $bundle->sourcePath ? $bundle->sourcePath : Yii::getAlias('@webroot');
            } else {
                $sourcePath = $bundle->basePath;
            }

            $this->refreshAssetBundle($bundle, $sourcePath, dirname($filename));

            $sourcePath = $bundle->basePath . '/' . $filename;
            $extension = pathinfo($filename, PATHINFO_EXTENSION);
            $filename = preg_replace('/\.(min\.)*' . preg_quote($extension, '/') . '$/i', '.min.' . $extension, $filename);
            $minPath = "{$bundle->basePath}/$filename";
            if ($this->testFile($minPath, $sourcePath)) {
                file_put_contents($minPath, $this->getMinifierByExtension($extension)->minify($sourcePath));

                $this->createGzFile($minPath);
            }
        }

        return $filename;
    }

    /**
     * 
     * @param string $minFile
     * @param string $srcFile
     * @return boolean
     */
    protected function testFile($minFile, $srcFile)
    {
        return !file_exists($minFile) || filemtime($srcFile) > filemtime($minFile);
    }

    /**
     * Returns the minifier object based on extension.
     * @param string $extension
     * @return minifiers\MinifierInterface
     */
    protected function getMinifierByExtension($extension)
    {
        return $extension == 'js' ? AssetMinifier::createObjet($this->js, minifiers\PhpJsMinifier::className()) : AssetMinifier::createObjet($this->css, minifiers\PhpCssMinifier::className());
    }

    /**
     * Creating compressed .gz version of the file in path.
     * @param string $path
     */
    protected function createGzFile($path)
    {
        if ($this->createGz) {
            file_put_contents($path . '.gz', gzencode(file_get_contents($path), 9), LOCK_EX);
        }
    }

    /**
     * 
     * @param \yii\web\AssetBundle $assetBundle
     * @param string $src
     * @param string $subDir
     * @return string
     */
    protected function refreshAssetBundle($assetBundle, $src, $subDir)
    {
        if ($assetBundle->baseUrl === '') {
            $dir = $this->hash($assetBundle->basePath, $subDir);
            $assetBundle->basePath = "{$this->_webroot}/$dir";
            $assetBundle->baseUrl = "{$this->_web}/$dir";

            if ($this->forceCopy || !is_dir($assetBundle->basePath)) {
                $this->copyDirectory($src, $assetBundle->basePath);
            }
        }
    }

    /**
     * 
     * @param string $src
     * @param string $dst
     */
    protected function copyDirectory($src, $dst)
    {
        if ($src === $dst || strpos($dst, $src) === 0) {
            foreach ($this->findDirs($src, $dst) as $dir => $src) {
                FileHelper::copyDirectory($src, $dst . '/' . $dir);
            }
        } else {
            FileHelper::copyDirectory($src, $dst);
        }
    }

    /**
     * 
     * @param string $path
     * @param string $src
     * @return string
     */
    protected function findDirs($path, $src)
    {

        $dirs = [];
        foreach (scandir($path) as $dir) {
            if ($dir === '.' || $dir === '..' || is_file($path . '/' . $dir) || $path . '/' . $dir === $src || strpos($src, $path . '/' . $dir) === 0) {
                continue;
            }

            $dirs[$dir] = $path . '/' . $dir;
        }

        return $dirs;
    }

    /**
     * Generate a CRC32 hash for the directory path. Collisions are higher
     * than MD5 but generates a much smaller hash string.
     * @param string $path string to be hashed.
     * @return string hashed string.
     */
    protected function hash($path)
    {
        $string = '';
        foreach ($this->findDirs($path, $this->_webroot) as $dir) {
            $string .= $dir . filemtime($dir);
            foreach (FileHelper::findFiles($dir, ['only' => ['*.js', '*.css']]) as $file) {
                $string .= $file . filemtime($file);
            }
        }

        return sprintf('%x', crc32($path . $string . Yii::getVersion()));
    }

}
