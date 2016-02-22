<?php

namespace lajax\assetminifier\minifiers;

use Yii;

/**
 * Minifying css using cli script.
 * 
 * @author Lajos Molnár <lajax.m@gmail.com>
 * @since 1.0
 */
class CliCssMinifier extends CliMinifier
{

    /**
     * @var string Command to execute css minifyer script.
     */
    public $command;

    /**
     * @inheritdoc
     */
    public function init()
    {

        parent::init();

        if (!$this->command) {
            $this->command = 'java -jar ' . Yii::getAlias('@vendor/packagist/yuicompressor-bin/bin/yuicompressor.jar') . ' --type css {from}';
        }

        Yii::info($this->command, 'asset-minifier');
    }

}
