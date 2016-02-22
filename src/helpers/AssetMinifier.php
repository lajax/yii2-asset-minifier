<?php

namespace lajax\assetminifier\helpers;

use Yii;

/**
 * Asset Minifier extension helper.
 * @author Lajos Molnár <lajax.m@gmail.com>
 * @since 1.0
 */
class AssetMinifier
{

    /**
     * Helper for creating objects.
     * @param mixed $object The object to be created or the array describint the object. If this is an object already,
     * then the object returned without any modification.
     * @param string $defaultClass The default class for the object. It will be used if the class of the object is 
     * not specified in the $object parameter.
     * @return static Created object.
     */
    public static function createObjet($object, $defaultClass)
    {
        if ($object === null) {
            $object = Yii::createObject($defaultClass);
        } else if (is_array($object) || is_string($object)) {
            if (is_array($object) && !isset($object['class'])) {
                $object['class'] = $defaultClass;
            }

            $object = Yii::createObject($object);
        }

        return $object;
    }

}
