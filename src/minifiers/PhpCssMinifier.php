<?php

namespace lajax\assetminifier\minifiers;

use CssMin;

/**
 * Minifying css using php script
 * 
 * @author Lajos Molnár <lajax.m@gmail.com>
 * @since 1.0
 */
class PhpCssMinifier extends \yii\base\Object implements MinifierInterface
{

    /**
     * @var array VssMin Filter configuration.
     */
    public $filters = [
        'ImportImports' => false,
        'RemoveComments' => true,
        'RemoveEmptyRulesets' => true,
        'RemoveEmptyAtBlocks' => true,
        'ConvertLevel3AtKeyframes' => false,
        'ConvertLevel3Properties' => false,
        'Variables' => true,
        'RemoveLastDelarationSemiColon' => true
    ];

    /**
     * @var array CssMin Plugin configuration.
     */
    public $plugins = [
        'Variables' => true,
        'ConvertFontWeight' => true,
        'ConvertHslColors' => true,
        'ConvertRgbColors' => true,
        'ConvertNamedColors' => true,
        'CompressColorValues' => true,
        'CompressExpressionValues' => true,
    ];

    /**
     * @inheritdoc
     */
    public function minify($path)
    {
        return CssMin::minify(file_get_contents($path), $this->filters, $this->plugins);
    }

}
