<?php

namespace MobileApi\Util;


use MobileApi\Exception\Exception;

class Version
{

    private static $version = null;

    /**
     * @var RequestInterface
     */
    private static $requestHandler = null;

    private function __construct()
    {
    }

    /**
     * @param $className
     *
     * @throws Exception
     */
    public static function setRequestHandler($className)
    {
        $implements = class_implements($className);
        if (!isset($implements[RequestInterface::class])) {
            throw new Exception('Request must be implemented RequestInterface');
        }
        self::$requestHandler = $className;
    }

    public static function get(): string
    {
        if (self::$version !== null) {
            return self::$version;
        }

        if (self::$requestHandler === null) {
            self::$requestHandler = Request::class;
        }

        self::$version = self::$requestHandler::getVersion();

        if (strpos(self::$version, '.') === false) {
            self::$version = self::transToStringStyle(self::$version);
        }

        return self::$version;
    }

    public static function isEqualTo($version): bool
    {
        return version_compare(self::get(), $version, '=');
    }

    public static function isNotEqualTo($version): bool
    {
        return version_compare(self::get(), $version, '!=');
    }

    public static function isGreaterThan($version): bool
    {
        return version_compare(self::get(), $version, '>');
    }

    public static function isGreaterThanOrEqualTo($version): bool
    {
        return version_compare(self::get(), $version, '>=');
    }

    public static function isLessThan($version): bool
    {
        return version_compare(self::get(), $version, '<');
    }

    public static function isLessThanOrEqualTo($version): bool
    {
        return version_compare(self::get(), $version, '<=');
    }

    public static function isIn(array $versions): bool
    {
        foreach ($versions as $version) {
            if (self::isEqualTo($version)) {
                return true;
            }
        }

        return false;
    }

    public static function isNotIn(array $versions): bool
    {
        foreach ($versions as $version) {
            if (self::isEqualTo($version)) {
                return false;
            }
        }

        return true;
    }

    /**
     * convert version to string
     *
     * 003003003->3.3.3
     *
     * @param $version
     *
     * @return string
     */
    public static function transToStringStyle($version): string
    {
        if (strpos($version, '.') !== false) {
            return $version;
        }

        $version = substr(str_pad($version, 9, '0', STR_PAD_LEFT), 0, 9);

        return array_reduce(str_split($version, 3), function ($carry, $item) {
            return ($carry === null ? '' : $carry . '.') . (int)$item;
        });
    }

    /**
     * convert version to number
     *
     * 3.3.3->003003003
     *
     * @param $version
     *
     * @return string
     */
    public static function transToNumberStyle($version): string
    {
        if (strpos($version, '.') === false) {
            return $version;
        }

        $initVersion = [0, 0, 0];

        $versionParseInfo = explode('.', $version);

        $versionParseInfo = array_slice($versionParseInfo + $initVersion, 0, 3);

        return sprintf('%03s%03s%03s', $versionParseInfo[0], $versionParseInfo[1], $versionParseInfo[2]);
    }
}