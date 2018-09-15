<?php

namespace MobileApi\Util;

class Server
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
     * Method  get
     *
     * @author wangxuan
     * @static
     *
     * @param string $key
     * @param string $default
     *
     * @return null|string
     */
    public static function get($key, $default = null)
    {
        $key = strtoupper($key);

        return isset($_SERVER[$key]) ? $_SERVER[$key] : $default;
    }

}
