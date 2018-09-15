<?php

namespace MobileApi\Util;

class Cookie
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
        return isset($_COOKIE[$key]) ? $_COOKIE[$key] : $default;
    }

    /**
     * Method  set
     *
     * @author wangxuan
     * @static
     *
     * @param string $name
     * @param string $value
     * @param int    $expire
     * @param string $path
     * @param string $domain
     * @param bool   $secure
     * @param bool   $httponly
     *
     * @return bool
     */
    public static function set($name, $value = '', $expire = 0, $path = '', $domain = '', $secure = false, $httponly = false)
    {
        return setcookie($name, $value, $expire, $path, $domain, $secure, $httponly);
    }

}
