<?php

namespace MobileApi\Util;

class Url
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
     * Method  getCurrentDomain
     *
     * @author wangxuan
     * @static
     * @return bool|string
     */
    public static function getCurrentDomain()
    {
        if (empty(Server::get('server_name')) || empty(Server::get('server_port'))) {
            return false;
        }

        $url = 'http';

        if (!empty(Server::get('https')) && strtolower(Server::get('https')) === 'on') {
            $url .= 's';
        }

        $url .= '://' . Server::get('server_name');

        if (Env::isDevelop()) {
            if ((int)Server::get('server_port') !== 80) {
                $url .= ':' . Server::get('server_port');
            }
        }

        return $url;
    }

    /**
     * Method  getCurrentUrl
     *
     * @author wangxuan
     * @static
     * @return bool|string
     */
    public static function getCurrentUrl()
    {
        if (empty(Server::get('server_name')) || empty(Server::get('server_port')) || empty(Server::get('request_uri'))) {
            return false;
        }

        $url = 'http';

        if (!empty(Server::get('https')) && strtolower(Server::get('https')) === 'on') {
            $url .= 's';
        }

        $url .= '://' . Server::get('server_name');

        if (Env::isDevelop()) {
            if ((int)Server::get('server_port') !== 80) {
                $url .= ':' . Server::get('server_port');
            }
        }

        $url .= Server::get('request_uri');

        return $url;
    }

}
