<?php

namespace MobileApi\Util;

use Yaf\Registry;

class Generator
{

    /**
     * Method __construct
     *
     * @author wangxuan
     */
    private function __construct()
    {
    }

    /**
     * Method  requestId
     *
     * @author wangxuan
     * @static
     * @return string
     */
    public static function requestId()
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*()+-';

        $random = $chars[mt_rand(0, 73)] . $chars[mt_rand(0, 73)] . $chars[mt_rand(0, 73)] . $chars[mt_rand(0, 73)] . $chars[mt_rand(0, 73)];

        return hash('md5', uniqid() . '-' . $random);
    }

    /**
     * Method  verifyCode
     *
     * @author wangxuan
     * @static
     * @return int
     */
    public static function verifyCode()
    {
        return mt_rand(1000, 9999);
    }

    /**
     * Method  qrCodeImageBase64String
     *
     * @author wangxuan
     * @static
     *
     * @param string $url
     * @param int    $width
     * @param int    $height
     * @param int    $margin
     *
     * @return string
     */
    public static function qrCodeImageBase64String($url, $width = 500, $height = 500, $margin = 0)
    {
        $renderer = new \BaconQrCode\Renderer\Image\Png();

        $renderer->setWidth($width);

        $renderer->setHeight($height);

        $renderer->setMargin($margin);

        $writer = new \BaconQrCode\Writer($renderer);

        $base64String = $writer->writeString($url);

        return !empty($base64String) ? 'data:image/png;base64,' . base64_encode($base64String) : '';
    }

}
