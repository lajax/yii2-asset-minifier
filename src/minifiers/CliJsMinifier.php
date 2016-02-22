<?php

namespace lajax\assetminifier\minifiers;

use Yii;

/**
 * Minifying Js using cli script.
 * 
 * @author Lajos Molnár <lajax.m@gmail.com>
 * @since 1.0
 */
class CliJsMinifier extends CliMinifier
{

    /**
     * @var string The command to execute minifyer script.
     */
    public $command;

    /**
     * @inheritdoc
     */
    public function init()
    {

        parent::init();

        if (!$this->command) {
            $this->command = 'java -jar ' . Yii::getAlias('@vendor/packagist/closurecompiler-bin/bin/compiler.jar') . ' --js {from}';
        }

        Yii::info($this->command, 'asset-minifier');
    }

}
