<?php
/**
 * Client.php
 *
 * @author: Anson
 * @date  : 2017-03-31 10:52
 */

namespace MobileApi\Util\Http;

class Client implements ClientInterface
{
    private static $instance;

    private $client;

    private function __construct()
    {
        $this->client = new \GuzzleHttp\Client();
    }

    public static function getInstance()
    {
        if (!empty(self::$instance)) {
            return self::$instance;
        }
        self::$instance = new static();

        return self::$instance;
    }

    /**
     * @param       $method
     * @param       $uri
     * @param array $options
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function request($method, $uri, array $options = [])
    {
        return $this->client->request($method, $uri, $options);
    }

    /**
     * @param       $method
     * @param       $uri
     * @param array $options
     *
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function requestAsync($method, $uri, array $options = [])
    {
        return $this->client->requestAsync($method, $uri, $options);
    }

    /**
     * @param       $uri
     * @param array $options
     *
     * @return string
     */
    public function get($uri, array $options = [])
    {
        $response = $this->client->get($uri, $options);

        return (string)$response->getBody();
    }

    /**
     * @param       $uri
     * @param       $params
     * @param array $options
     *
     * @return string
     */
    public function post($uri, $params, array $options = [])
    {
        if (!empty($params)) {
            $options['form_params'] = $params;
        }
        $response = $this->client->post($uri, $options);

        return (string)$response->getBody();
    }

}