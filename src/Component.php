<?php

namespace lajax\assetminifier;

use Yii;
use yii\web\View;
use lajax\assetminifier\helpers\AssetMinifier;

/**
 * Component performing the minification and combination of asset files.
 * 
 * Minimal configs:
 * 
 * ~~~
 * 'bootstrap' => ['assetMinifier'],
 * 'components' => [
 *      'assetMinifier' => [
 *          'class' => lajax\assetminifier\Component::className()
 *      ]
 * ]
 * ~~~
 * 
 * @author Lajos Molnár <lajax.m@gmail.com>
 * @since 1.0
 */
class Component extends \yii\base\BaseObject
{

    /**
     * @var boolean Enable minification of JavaScript files.
     */
    public $minifyJs = true;

    /**
     * @var boolean Enable minification of StyleSheet files.
     */
    public $minifyCss = true;

    /**
     * @var boolean Enable combination of asset files.
     */
    public $combine = true;

    /**
     * @var boolean Create corresponding .gz files for asset files.
     */
    public $createGz = false;

    /**
     * @var Minifier Object performing minification of asset files.
     */
    public $minifier;

    /**
     * @var Combiner Object performing concatenation of asset files.
     */
    public $combiner;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        Yii::$app->view->on(View::EVENT_END_BODY, function () {
            $this->process();
        });

        if (is_array($this->minifier)) {
            $this->minifier['createGz'] = $this->createGz;
        }

        if (!$this->combiner || is_array($this->combiner)) {
            $this->combiner['createGz'] = $this->createGz;
            $this->combiner['minifiedJs'] = isset($this->combiner['minifiedJs']) ? $this->combiner['minifiedJs'] : $this->minifyJs;
            $this->combiner['minifiedCss'] = isset($this->combiner['minifiedCss']) ? $this->combiner['minifiedCss'] : $this->minifyCss;
        }
    }

    /**
     * Minifying and concatenating asset files.
     */
    public function process()
    {
        $this->minifier = AssetMinifier::createObjet($this->minifier, Minifier::className());
        foreach (Yii::$app->view->assetBundles as $key => $assetBundle) {
            if ($this->minifyJs && $assetBundle->js) {
                Yii::$app->view->assetBundles[$key] = $this->minifier->minifyJs($assetBundle);
            }

            if ($this->minifyCss && $assetBundle->css) {
                Yii::$app->view->assetBundles[$key] = $this->minifier->minifyCss($assetBundle);
            }
        }

        if ($this->combine) {
            $this->combiner = AssetMinifier::createObjet($this->combiner, Combiner::className());
            $this->combiner->process($this->minifyJs, $this->minifyCss);
        }
    }

}
