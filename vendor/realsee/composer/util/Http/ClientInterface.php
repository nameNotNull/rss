<?php
/**
 * ClientInterface.php
 *
 * @author: Anson
 * @date  : 2017-03-31 10:53
 */

namespace MobileApi\Util\Http;

interface ClientInterface
{
    const VERSION = '0.1';

    public function request($method, $uri, array $options = []);

    public function requestAsync($method, $uri, array $options = []);

    public function get($uri, array $options = []);

    public function post($uri, $params, array $options = []);

}