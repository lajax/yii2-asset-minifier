<?php

namespace lajax\assetminifier;

use Yii;
use yii\helpers\Url;
use lajax\assetminifier\helpers\AssetMinifier;

/**
 * Class performing the minification of asset files.
 * 
 * @author Lajos Molnár <lajax.m@gmail.com>
 * @since 1.0
 */
class Minifier extends \yii\base\Object
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
     * Minification of js files of the assetBundle received as a parameter.
     * @param \yii\web\AssetBundle $assetBundle
     * @return \yii\web\AssetBundle
     */
    public function minifyJs($assetBundle)
    {
        foreach ($assetBundle->js as $key => $js) {
            $assetBundle->js[$key] = $this->minify($assetBundle, $js);
        }

        return $assetBundle;
    }

    /**
     * Minification of js files of the assetBundle received as a parameter.
     * @param \yii\web\AssetBundle $assetBundle
     * @return \yii\web\AssetBundle
     */
    public function minifyCss($assetBundle)
    {
        foreach ($assetBundle->css as $key => $css) {
            $assetBundle->css[$key] = $this->minify($assetBundle, $css);
        }

        return $assetBundle;
    }

    /**
     * Function performing the minification of asset files.
     * @param \yii\web\AssetBundle $bundle
     * @param string $filename
     * @return string
     */
    protected function minify($bundle, $filename)
    {
        if (Url::isRelative($filename)) {
            if ($this->workPath === self::WORKPATH_SOURCE) {
                $sourcePath = $bundle->sourcePath ? $bundle->sourcePath : Yii::getAlias('@webroot');
            } else {
                $sourcePath = $bundle->basePath;
            }

            $sourcePath .= '/' . $filename;

            $extension = pathinfo($filename, PATHINFO_EXTENSION);
            $filename = preg_replace('/\.(min\.)*' . preg_quote($extension, '/') . '$/i', '.min.' . $extension, $filename);
            $minPath = $bundle->basePath . '/' . $filename;
            if (!file_exists($minPath) || filemtime($sourcePath) > filemtime($minPath)) {
                file_put_contents($minPath, $this->getMinifierByExtension($extension)->minify($sourcePath));

                if ($this->createGz) {
                    $this->_createGzFile($minPath);
                }
            }
        }

        return $filename;
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
    private function _createGzFile($path)
    {
        file_put_contents($path . '.gz', gzencode(file_get_contents($path), 9), LOCK_EX);
    }

}
