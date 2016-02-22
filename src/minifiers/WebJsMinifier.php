<?php

namespace lajax\assetminifier\minifiers;

/**
 * Minifying js via web API.
 * 
 * @author Lajos Molnár <lajax.m@gmail.com>
 * @since 1.0
 */
class WebJsMinifier extends WebMinifier
{

    /**
     * @var string URL of the minifying web API.
     */
    public $url = 'http://javascript-minifier.com/raw';

}
