<?php

namespace lajax\assetminifier;

use Yii;
use yii\helpers\Url;
use yii\helpers\FileHelper;
use yii\web\AssetBundle;

/**
 * Class for concatenating asset files.
 * 
 * @author Lajos Molnár <lajax.m@gmail.com>
 * @since 1.0
 */
class Combiner extends \yii\base\Object
{

    /**
     * @var boolean enable minification of JavaScript files.
     */
    public $minifiedJs = true;

    /**
     * @var boolean enable minification of StyleSheet files.
     */
    public $minifiedCss = true;

    /**
     * @var boolean create the corresponding .gz files for asset files.
     */
    public $createGz = false;

    /**
     * @var string Parent library for combined asset files.
     */
    public $combinedFilesPath = '/lajax-asset-minifier';

    /**
     * @var array List of JavaScript és StyleSheet files grouped by positions.
     */
    private $_files = [];

    /**
     * @var \yii\web\AssetBundle[] List of AssetBundle objects handling combined files.
     */
    private $_assetBundles = [];

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        FileHelper::createDirectory(Yii::getAlias('@webroot/assets' . $this->combinedFilesPath), 0777);
    }

    /**
     * @return boolean whether the current request requires pjax response from this widget.
     */
    protected function requiresPjax()
    {
        $headers = Yii::$app->getRequest()->getHeaders();
        return $headers->get('X-Pjax', false);
    }

    /**
     * Method to concatenate asset files.
     */
    public function process()
    {

        foreach (array_keys(Yii::$app->view->assetBundles) as $name) {
            $this->combineAssetBundles($name);
        }
        // If empty position is not created, do it now for CSS
        if (!isset($this->_assetBundles[''])) {
            $this->getAssetBundles();
        }
        $this->saveAssetFiles();
    }

    /**
     * Recursive method for grouping asset files.
     * @param string $name The name of the processed AssetBundle.
     */
    protected function combineAssetBundles($name)
    {
        $assetBundle = Yii::$app->view->assetBundles[$name];

        if ($assetBundle) {
            foreach ($assetBundle->depends as $dep) {
                $this->combineAssetBundles($dep);
            }

            $position = isset($assetBundle->jsOptions['position']) ? $assetBundle->jsOptions['position'] : null;
            $this->getAssetBundles($position);

            $this->combineAssetBundle($assetBundle, 'js', $position);
            $this->combineAssetBundle($assetBundle, 'css', null);
        }
    }

    /**
     * Combining asset files depending on type.
     * @param AssetBundle $assetBundle The AssetBundle to be processed.
     * @param string $property The type of the assets to be processed, `css` or `js`.
     * @param integer $position The position of the AssetBundle.
     */
    protected function combineAssetBundle($assetBundle, $property, $position)
    {
        foreach ($assetBundle->$property as $filename) {
            if (is_array($filename)) {
                $filename = array_shift($filename);
            }

            if (Url::isRelative($filename)) {
                $path = $assetBundle->basePath . '/' . $filename;
                $this->_files[$position][$property][$path . @filemtime($path)] = [
                    'basePath' => $path,
                    'baseUrl' => dirname($assetBundle->baseUrl . '/' . $filename)
                ];
            } else {
                $this->_assetBundles[$position]->{$property}[] = $filename;
            }
        }
    }

    /**
     * Method to concatenate and save combined Asset.
     */
    protected function saveAssetFiles()
    {
        Yii::$app->view->assetBundles = [];
        foreach ($this->_files as $position => $files) {
            if (!$this->requiresPjax() && isset($files['js'])) {
                $this->mergeJsFiles($position, $files['js']);
            }

            if (isset($files['css'])) {
                $this->mergeCssFiles($position, $files['css']);
            }

            Yii::$app->view->assetBundles[$position] = $this->_assetBundles[$position];
        }
    }

    /**
     * Combination and saving of JavaScript files.
     * @param integer $position
     * @param array $files List of asset files to be combined.
     */
    protected function mergeJsFiles($position, $files)
    {
        $filename = md5(implode('-', array_keys($files))) . ($this->minifiedJs ? '.min.com' : '.com') . '.js';
        if (!file_exists($this->_assetBundles[$position]->basePath . '/' . $filename)) {
            $content = '';
            foreach ($files as $data) {
                $content .= "/*** BEGIN FILE: " . basename($data['basePath']) . " ***/\n\n"
                        . file_get_contents($data['basePath'])
                        . "\n\n/*** END FILE: " . basename($data['basePath']) . " ***/\n\n";
            }

            $this->saveFile($this->_assetBundles[$position]->basePath . '/' . $filename, $content);
        }

        $this->_assetBundles[$position]->js[] = $filename;
    }

    /**
     * Combination and saving of StyleSheet files.
     * @param integer $position
     * @param array $files List of asset files to be combined.
     */
    protected function mergeCssFiles($position, $files)
    {
        $filename = md5(implode('-', array_keys($files))) . ($this->minifiedCss ? '.min.com' : '.com') . '.css';
        if (!file_exists($this->_assetBundles[$position]->basePath . '/' . $filename)) {
            $content = '';
            foreach ($files as $data) {
                $content .= "/*** BEGIN FILE: " . basename($data['basePath']) . " ***/\n\n"
                        . $this->adjustCssUrl(file_get_contents($data['basePath']) . "\n", $data['baseUrl'], $this->_assetBundles[$position]->baseUrl)
                        . "\n\n/*** END FILE: " . basename($data['basePath']) . " ***/\n\n";
            }

            $this->saveFile($this->_assetBundles[$position]->basePath . '/' . $filename, $content);
        }

        $this->_assetBundles[$position]->css[] = $filename;
    }

    /**
     * Saving merged and combined asset files to disk.
     * @param string $path Path of the new asset file.
     * @param string $content Content of the new asset file.
     */
    protected function saveFile($path, $content)
    {
        file_put_contents($path, $content, LOCK_EX);

        if ($this->createGz) {
            file_put_contents($path . '.gz', gzencode($content, 9), LOCK_EX);
        }
    }

    /**
     * Creating MinifierAsset bundles based on position.
     * @param string $position Position of asset files.
     * @return AssetBundle
     */
    protected function getAssetBundles($position = null)
    {
        if (!isset($this->_assetBundles[$position])) {
            $config = [
                'basePath' => Yii::getAlias('@webroot/assets' . $this->combinedFilesPath),
                'baseUrl' => Yii::getAlias('@web/assets' . $this->combinedFilesPath)
            ];

            if ($position) {
                $config['jsOptions'] = ['position' => $position];
            }

            $this->_assetBundles[$position] = new AssetBundle($config);
        }

        return $this->_assetBundles[$position];
    }

    /**
     * Returns canonicalized absolute pathname.
     * Unlike regular `realpath()` this method does not expand symlinks and does not check path existence.
     * @param string $path raw path
     * @return string canonicalized absolute pathname.
     */
    private function findRealPath($path)
    {
        $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
        $pathParts = explode(DIRECTORY_SEPARATOR, $path);

        $realPathParts = [];
        foreach ($pathParts as $pathPart) {
            if ($pathPart === '..') {
                array_pop($realPathParts);
            } else {
                array_push($realPathParts, $pathPart);
            }
        }
        return implode(DIRECTORY_SEPARATOR, $realPathParts);
    }

    /**
     * Adjusts CSS content allowing URL references pointing to the original resources.
     * @param string $cssContent source CSS content.
     * @param string $inputFilePath input CSS file name.
     * @param string $outputFilePath output CSS file name.
     * @return string adjusted CSS content.
     */
    protected function adjustCssUrl($cssContent, $inputFilePath, $outputFilePath)
    {
        $inputFilePath = str_replace('\\', '/', $inputFilePath);
        $outputFilePath = str_replace('\\', '/', $outputFilePath);

        $sharedPathParts = [];
        $inputFilePathParts = explode('/', $inputFilePath);
        $inputFilePathPartsCount = count($inputFilePathParts);
        $outputFilePathParts = explode('/', $outputFilePath);
        $outputFilePathPartsCount = count($outputFilePathParts);
        for ($i = 0; $i < $inputFilePathPartsCount && $i < $outputFilePathPartsCount; $i++) {
            if ($inputFilePathParts[$i] == $outputFilePathParts[$i]) {
                $sharedPathParts[] = $inputFilePathParts[$i];
            } else {
                break;
            }
        }
        $sharedPath = implode('/', $sharedPathParts);

        $inputFileRelativePath = trim(str_replace($sharedPath, '', $inputFilePath), '/');
        $outputFileRelativePath = trim(str_replace($sharedPath, '', $outputFilePath), '/');
        if (empty($inputFileRelativePath)) {
            $inputFileRelativePathParts = [];
        } else {
            $inputFileRelativePathParts = explode('/', $inputFileRelativePath);
        }
        if (empty($outputFileRelativePath)) {
            $outputFileRelativePathParts = [];
        } else {
            $outputFileRelativePathParts = explode('/', $outputFileRelativePath);
        }

        $callback = function ($matches) use ($inputFileRelativePathParts, $outputFileRelativePathParts) {
            $fullMatch = $matches[0];
            $inputUrl = $matches[1];

            if (strpos($inputUrl, '/') === 0 || preg_match('/^https?:\/\//is', $inputUrl) || preg_match('/^data:/is', $inputUrl)) {
                return $fullMatch;
            }
            if ($inputFileRelativePathParts === $outputFileRelativePathParts) {
                return $fullMatch;
            }

            if (empty($outputFileRelativePathParts)) {
                $outputUrlParts = [];
            } else {
                $outputUrlParts = array_fill(0, count($outputFileRelativePathParts), '..');
            }
            $outputUrlParts = array_merge($outputUrlParts, $inputFileRelativePathParts);

            if (strpos($inputUrl, '/') !== false) {
                $inputUrlParts = explode('/', $inputUrl);
                foreach ($inputUrlParts as $key => $inputUrlPart) {
                    if ($inputUrlPart == '..') {
                        array_pop($outputUrlParts);
                        unset($inputUrlParts[$key]);
                    }
                }
                $outputUrlParts[] = implode('/', $inputUrlParts);
            } else {
                $outputUrlParts[] = $inputUrl;
            }
            $outputUrl = implode('/', $outputUrlParts);

            return str_replace($inputUrl, $outputUrl, $fullMatch);
        };

        $cssContent = preg_replace_callback('/url\(["\']?([^)^"^\']*)["\']?\)/is', $callback, $cssContent);

        return $cssContent;
    }

}
