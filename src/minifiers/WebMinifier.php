<?php

namespace lajax\assetminifier\minifiers;

/**
 * Minify asset via web API.
 * 
 * @author Lajos Molnár <lajax.m@gmail.com>
 * @since 1.0
 */
class WebMinifier extends \yii\base\Object implements MinifierInterface
{

    /**
     * @inheritdoc
     */
    public function minify($path)
    {
        return $this->curl([
                    'input' => file_get_contents($path)
        ]);
    }

    /**
     * Method to maintain connection with web API.
     * @param array $params Array containing the asset file input to be minimised.
     * @return string The asset file to be minimised.
     */
    protected function curl($params)
    {
        $postData = [
            'http' => [
                'method' => 'POST',
                'header' => 'Content-type: application/x-www-form-urlencoded',
                'content' => http_build_query($params)
            ]
        ];

        return file_get_contents($this->url, false, stream_context_create($postData));
    }

}
