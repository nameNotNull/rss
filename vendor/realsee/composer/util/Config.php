<?php

namespace MobileApi\Util;

use Yaf\Config\Ini;

class Config
{

    private static $_instances = [];

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
     * @param $key
     * @param $default
     *
     * @return mixed
     */
    public static function get($key, $default = null)
    {
        $result = null;

        if (strpos($key, '.') !== false) {
            list($file, $path) = explode('.', $key, 2);
        } else {
            $file = $key;
        }

        $filename = ROOT_PATH . '/config/' . $file . '.ini';

        if (!file_exists($filename)) {
            return null;
        }

        if (!isset(self::$_instances[$file])) {
            self::$_instances[$file] = new Ini($filename);
        }

        if (!empty($path)) {
            list($environment) = explode('.', $path, 2);

            if ($environment !== Env::getEnvironment()) {
                $result = self::$_instances[$file]->get(Env::getEnvironment() . '.' . $path);
            }

            if ($result === null) {
                $result = self::$_instances[$file]->get($path);
            }
        } else {
            $result = self::$_instances[$file]->get(Env::getEnvironment());

            if ($result === null) {
                $result = self::$_instances[$file];
            }
        }

        if (is_a($result, 'Yaf\Config\Ini')) {
            return $result->toArray();
        }

        if ($result !== null) {
            return $result;
        }

        return $default;
    }

    /**
     * Method  getFull
     * 根据key直接获取value
     *
     * @author chenyi
     * @static
     *
     * @param      $key
     * @param null $default
     *
     * @return mixed
     */
    public static function getFull($key, $default = null)
    {
        return self::get($key, $default);
    }

}
