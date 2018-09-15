<?php

namespace MobileApi\Util;

class Env
{

    /**
     * Variable  _environment
     *
     * @author   wangxuan
     * @static
     * @var      string
     */
    private static $_environment = null;

    /**
     * Method __construct
     *
     * @author wangxuan
     */
    private function __construct()
    {

    }

    /**
     * Method  getEnvironment
     *
     * @author wangxuan
     * @static
     * @return string
     */
    public static function getEnvironment()
    {
        if (self::$_environment !== null) {
            return self::$_environment;
        }

        if (defined('APPLICATION_ENVIRONMENT')) {
            self::$_environment = APPLICATION_ENVIRONMENT;
        } else {
            self::$_environment = ini_get('yaf.environ');
        }

        return self::$_environment;
    }

    /**
     * Method  isProduct
     *
     * @author wangxuan
     * @static
     * @return bool
     */
    public static function isProduct()
    {
        $environment = self::getEnvironment();

        if ('product' === $environment || 'product-aws' === $environment || 'preline' === $environment) {
            return true;
        }

        return false;
    }

    /**
     * Method  isDevelop
     *
     * @author wangxuan
     * @static
     * @return bool
     */
    public static function isDevelop()
    {
        $environment = self::getEnvironment();

        if ('develop' === $environment || 'alpha' === $environment) {
            return true;
        }

        return false;
    }

    /**
     * Method  isTest
     *
     * @author wangxuan
     * @static
     * @return bool
     */
    public static function isTest()
    {
        return 'test' === self::getEnvironment();
    }


    /**
     * Method  isPreline
     *
     * @author wangxuan
     * @static
     * @return bool
     */
    public static function isPreline()
    {
        return 'preline' === self::getEnvironment();
    }

}
