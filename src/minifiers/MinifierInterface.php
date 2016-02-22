<?php

namespace lajax\assetminifier\minifiers;

/**
 * Interface for minifying asset files.
 * @author Lajos Molnár <lajax.m@gmail.com>
 * @since 1.0
 */
interface MinifierInterface
{

    /**
     * Function for minifying the asset file (css, js) received as a parameter.
     * @param string $path The path of the asset file to be minified.
     * @return string The content of the asset file to be minified.
     */
    public function minify($path);
}
