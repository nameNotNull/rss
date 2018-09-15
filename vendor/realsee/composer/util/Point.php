<?php

namespace MobileApi\Util;

class Point
{

    /**
     * 地球半径，平均半径为6370693.5米
     */
    const EARTH_RADIUS = 6370693.5;

    /**
     * Method __construct
     *
     * @author wangxuan
     */
    private function __construct()
    {

    }

    /**
     * Method  getSquarePoints
     * 计算某个经纬度的周围某段距离的正方形的四个点
     *
     * @see http://blog.charlee.li/location-search/
     * @see http://fukun.org/archives/06152067.html
     *
     * @param float $longitude 经度
     * @param float $latitude  纬度
     * @param int   $distance  该点所在圆的半径，该圆与此正方形内切，默认值为1000米
     *
     * @return array 正方形的四个点的经纬度坐标
     */
    public static function getSquarePoints($longitude, $latitude, $distance = 1000)
    {
        if (!isset($longitude, $latitude, $distance)) {
            return false;
        }

        // 将弧度数转换为相应的角度数
        $longitudeDifference = rad2deg(2 * asin(sin($distance / (2 * self::EARTH_RADIUS)) / cos(deg2rad($latitude))));

        $latitudeDifference = rad2deg($distance / self::EARTH_RADIUS);

        return [
            'left-top'     => [
                'longitude' => $longitude - $longitudeDifference,
                'latitude'  => $latitude + $latitudeDifference,
            ],
            'right-top'    => [
                'longitude' => $longitude + $longitudeDifference,
                'latitude'  => $latitude + $latitudeDifference,
            ],
            'left-bottom'  => [
                'longitude' => $longitude - $longitudeDifference,
                'latitude'  => $latitude - $latitudeDifference,
            ],
            'right-bottom' => [
                'longitude' => $longitude + $longitudeDifference,
                'latitude'  => $latitude - $latitudeDifference,
            ],
        ];
    }

}
