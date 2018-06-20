<?php

namespace lajax\assetminifier\minifiers;

/**
 * Minifying Assets using cli script.
 * 
 * @author Lajos Molnár <lajax.m@gmail.com>
 * @since 1.0
 */
class CliMinifier extends \yii\base\BaseObject implements MinifierInterface
{

    /**
     * @inheritdoc
     * @throws \BadMethodCallException
     */
    public function init()
    {
        parent::init();

        if (exec('command -v java >/dev/null && echo "yes" || echo "no"') == 'no') {
            throw new \BadMethodCallException('Java Not Installed');
        }
    }

    /**
     * @inheritdoc
     */
    public function minify($path)
    {
        return shell_exec(strtr($this->command, [
            '{from}' => escapeshellarg($path),
        ]));
    }

}
