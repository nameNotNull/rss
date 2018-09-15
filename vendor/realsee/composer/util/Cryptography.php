<?php

namespace MobileApi\Util;

use Hashids\Hashids;

/**
 * Class Cryptography
 * 加密，仅支持数字且范围不超过int
 *
 * @package MobileApi\Util
 */
class Cryptography
{

    private static $cryptographicClient = null;

    /**
     * @param        $data
     * @param string $key
     *
     * @return bool|string
     */
    public static function encrypt($data, $key = '')
    {
        $result = static::getInstance($key)->encode($data);

        return empty($result) ? false : $result;
    }

    /**
     * @param string $hash
     * @param string $key
     *
     * @return bool|string|array
     */
    public static function decrypt($hash, $key = '')
    {
        $result = static::getInstance($key)->decode($hash);

        if (empty($result)) {
            $result = false;
        } elseif (count($result) === 1) {
            $result = $result[0];
        }

        return $result;
    }

    private static function getInstance($key = '')
    {
        if (empty($key)) {
            $key = Config::get('application.crypt.user_id.key');
        }

        if (!isset(self::$cryptographicClient[$key])) {
            self::$cryptographicClient[$key] = new Hashids($key);
        }

        return self::$cryptographicClient[$key];
    }

}