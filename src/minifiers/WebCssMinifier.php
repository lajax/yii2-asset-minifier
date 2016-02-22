<?php

namespace lajax\assetminifier\minifiers;

/**
 * Minifying css via web API.
 * 
 * @author Lajos Molnár <lajax.m@gmail.com>
 * @since 1.0
 */
class WebCssMinifier extends WebMinifier
{

    /**
     * @var string URL of the css minifyer web API.
     */
    public $url = 'http://cssminifier.com/raw';

}
