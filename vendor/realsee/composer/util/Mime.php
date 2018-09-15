<?php

namespace MobileApi\Util;

class Mime
{

    private static $releation = [
        'application/zip'          => 'zip',
        'application/java-archive' => 'jar',
        'text/plain'               => 'log',
        'application/x-gzip'       => 'gz',
        'image/jpeg'               => 'jpg',
        'image/gif'                => 'gif',
        'image/png'                => 'png',
    ];

    /**
     * Method __construct
     *
     * @author wangxuan
     */
    private function __construct()
    {

    }

    /**
     * Method  getExtensionByType
     *
     * @author wangxuan
     * @static
     *
     * @param string $type
     *
     * @return string|null
     */
    public static function getExtensionByType($type)
    {
        if (isset(self::$releation[$type])) {
            return self::$releation[$type];
        } else {
            return null;
        }
    }

    /**
     * Method  getTypeByExtension
     *
     * @author wangxuan
     * @static
     *
     * @param string $extension
     *
     * @return string|null
     */
    public static function getTypeByExtension($extension)
    {
        $extension = ltrim($extension);

        $releation = array_flip(self::$releation);

        if (isset($releation[$extension])) {
            return $releation[$extension];
        } else {
            return null;
        }
    }

}
