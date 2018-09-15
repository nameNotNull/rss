<?php

namespace MobileApi\Util;

class Convert
{

    /**
     * Method __construct
     *
     * @author dingjing
     */
    private function __construct()
    {

    }

    /**
     * Method  underlineToCamel
     *
     * @author wangxuan
     * @static
     *
     * @param string $string
     *
     * @return mixed
     */
    public static function underlineToCamel($string)
    {
        return preg_replace_callback('/_([a-zA-Z])/', function ($matches) {
            return strtoupper($matches[1]);
        }, $string);
    }

    /**
     * Method  camelToUnderline
     *
     * @author wangxuan
     * @static
     *
     * @param string $string
     *
     * @return mixed
     */
    public static function camelToUnderline($string)
    {
        return strtolower(trim(preg_replace('/([A-Z])/', "_\\1", $string), '_'));
    }

    /**
     * Method  underlineToCamelByArray
     *
     * @author wangxuan
     * @static
     *
     * @param array $data
     *
     * @return mixed
     */
    public static function underlineToCamelByArray($data)
    {
        $keys = array_keys($data);

        $values = array_values($data);

        unset($data);

        $keys = array_map([
            'self',
            'underlineToCamel',
        ], $keys);

        foreach ($values as $key => $value) {
            if (is_array($value)) {
                $values[$key] = self::underlineToCamelByArray($value);
            }
        }

        return array_combine($keys, $values);
    }

    /**
     * Method  camelToUnderlineByArray
     *
     * @author wangxuan
     * @static
     *
     * @param array $data
     *
     * @return mixed
     */
    public static function camelToUnderlineByArray($data)
    {
        $keys = array_keys($data);

        $values = array_values($data);

        unset($data);

        $keys = array_map([
            'self',
            'camelToUnderline',
        ], $keys);

        foreach ($values as $key => $value) {
            if (is_array($value)) {
                $values[$key] = self::camelToUnderlineByArray($value);
            }
        }

        return array_combine($keys, $values);
    }

    public static function toBoolean($value)
    {
        if ($value === null || is_bool($value)) {
            return $value;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Method  toInteger
     *
     * @author wangxuan
     * @static
     *
     * @param $value
     *
     * @return int|null
     */
    public static function toInteger($value)
    {
        if ($value === null || is_integer($value)) {
            return $value;
        }

        return (integer)$value;
    }

    /**
     * Method  toDouble
     *
     * @author wangxuan
     * @static
     *
     * @param $value
     *
     * @return float|null
     */
    public static function toDouble($value)
    {
        if ($value === null || is_double($value)) {
            return $value;
        }

        return (double)$value;
    }

}
