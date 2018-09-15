<?php

namespace MobileApi\Api;

use GuzzleHttp\TransferStats;
use MobileApi\Exception\Http\HttpInvalidParamException;
use MobileApi\Exception\Http\HttpRequestMethodException;
use MobileApi\Exception\Http\HttpResponseException;
use MobileApi\Exception\Http\HttpRuntimeException;
use MobileApi\Exception\Http\HttpUrlException;
use MobileApi\Util\Config;
use MobileApi\Util\Convert;
use MobileApi\Util\Http\AsyncHandler;
use MobileApi\Util\Log;
use MobileApi\Util\Message;
use MobileApi\Util\Request;
use MobileApi\Util\RequestInterface;
use Psr\Http\Message\ResponseInterface;

abstract class Base
{
    protected static $_connectTimeout = 2;

    protected static $_requestTimeout = 3;

    protected static $_requestRetryCount = 1;

    protected static $_isRestful = false;

    protected static $_dataKey = 'data';

    protected static $_codeKey = 'code';

    protected static $_messageKey = 'message';

    protected static $_successCode = 0;

    protected static $_excludeCode = null;

    protected static $_moduleName = null;

    protected static $_moduleConfigs = null;

    protected static $_baseUri = null;

    protected static $_isDubboBackend = false;

    protected static $_enable = true;

    protected static $_disableCode = '90001';

    protected static $_disableMessage = '服务不可用，请稍后再试';
    /**
     * @var \MobileApi\Util\RequestInterface
     */
    protected static $requestHandler;

    /**
     * Method  __callStatic
     *
     * @author wangxuan
     *
     * @param string $name
     * @param array  $arguments
     *
     * @throws \Exception
     * @return mixed
     */
    public static function __callStatic($name, array $arguments)
    {
        return static::request(Convert::camelToUnderline($name), isset($arguments[0]) ? $arguments[0] : $arguments);
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
        static::$requestHandler = $className;
    }

    /**
     * @return \MobileApi\Util\RequestInterface
     */
    protected static function getRequestHandler()
    {
        if (null === static::$requestHandler) {
            return Request::class;
        }

        return static::$requestHandler;
    }

    /**
     * Method  _initConfig
     *
     * @author wangxuan
     * @static
     *
     * @param $key
     *
     * @return mixed
     * @throws HttpRequestMethodException
     * @throws HttpUrlException
     */
    protected static function getConfig($key)
    {
        if (!isset(static::$_moduleConfigs[static::$_moduleName])) {
            static::$_moduleConfigs[static::$_moduleName] = Config::get('api-' . static::$_moduleName);
        }

        if (empty(static::$_moduleConfigs[static::$_moduleName][$key])) {
            throw new HttpUrlException('request config not found');
        }

        $moduleConfig = static::$_moduleConfigs[static::$_moduleName];

        $requestConfig = static::$_moduleConfigs[static::$_moduleName][$key];

        $requestConfig['enable'] = (bool)($requestConfig['enable'] ?? $moduleConfig['enable'] ?? static::$_enable);


        // check circuitBreaker
        if ($requestConfig['enable'] === false) {
            $disableCode = (int)($requestConfig['disable_code'] ?? $moduleConfig['disable_code'] ?? static::$_disableCode);

            $disableMessage = $requestConfig['disable_message'] ?? $moduleConfig['disable_message'] ?? static::$_disableMessage;

            throw new HttpResponseException($disableMessage, $disableCode);
        }

        $requestConfig['connect_timeout'] = $requestConfig['connect_timeout'] ?? $moduleConfig['connect_timeout'] ?? static::$_connectTimeout;

        $requestConfig['request_timeout'] = $requestConfig['request_timeout'] ?? $moduleConfig['request_timeout'] ?? static::$_requestTimeout;

        $requestConfig['request_retry_count'] = $requestConfig['request_retry_count'] ?? $moduleConfig['request_retry_count'] ?? static::$_requestRetryCount;

        $requestConfig['is_restful'] = (bool)($requestConfig['is_restful'] ?? $moduleConfig['is_restful'] ?? false);

        $requestConfig['data_key'] = $requestConfig['data_key'] ?? $moduleConfig['data_key'] ?? static::$_dataKey;

        $requestConfig['code_key'] = $requestConfig['code_key'] ?? $moduleConfig['code_key'] ?? static::$_codeKey;

        $requestConfig['exclude_code'] = $requestConfig['exclude_code'] ?? $moduleConfig['exclude_code'] ?? static::$_excludeCode;

        $requestConfig['message_key'] = $requestConfig['message_key'] ?? $moduleConfig['message_key'] ?? static::$_messageKey;

        $requestConfig['success_code'] = $requestConfig['success_code'] ?? $moduleConfig['success_code'] ?? static::$_successCode;

        $requestConfig['base_uri'] = $requestConfig['base_uri'] ?? $moduleConfig['base_uri'] ?? static::$_baseUri;

        $requestConfig['method'] = strtolower(trim($requestConfig['method'])) ?? null;

        $requestConfig['is_dubbo_backend'] = (bool)($requestConfig['is_dubbo_backend'] ?? $moduleConfig['is_dubbo_backend'] ?? static::$_isDubboBackend);

        $requestConfig['access_key_id'] = $requestConfig['access_key_id'] ?? $moduleConfig['access_key_id'] ?? '';

        $requestConfig['app_secret_key'] = $requestConfig['app_secret_key'] ?? $moduleConfig['app_secret_key'] ?? '';

        // check uri
        if (empty($requestConfig['base_uri'])) {
            throw new HttpUrlException('request base uri not found');
        }

        // check uri
        if (empty($requestConfig['uri'])) {
            throw new HttpUrlException('request uri not found');
        }

        // check method
        if (empty($requestConfig['method'])) {
            throw new HttpRequestMethodException('request method not found');
        }

        return $requestConfig;
    }

    /**
     * @param       $key
     * @param array $parameters
     *
     * @return array|AsyncHandler
     */
    public static function requestAsync($key, $parameters = [])
    {
        return self::request($key, $parameters, true);
    }

    /**
     * @param string       $key
     * @param array|string $parameters
     * @param bool         $async
     *
     * @return array|AsyncHandler
     * @throws HttpInvalidParamException
     * @throws HttpUrlException
     */
    public static function request($key, $parameters = [], $async = false)
    {
        // init request
        $config = static::getConfig($key);

        // check mapping
        if (!empty($config['mapping'])) {
            $mappingParameters = [];

            foreach (explode(',', $config['mapping']) as $mapping) {
                list($mappingKey, $parameterKey) = explode(':', $mapping);

                if (isset($parameters[$parameterKey])) {
                    $mappingParameters[$mappingKey] = $parameters[$parameterKey];

                    unset($parameters[$parameterKey]);
                }
            }
            $parameters = array_merge($parameters, $mappingParameters);
        }

        // check parameter
        if (!empty($config['parameter'])) {
            $acceptParameters = explode(',', $config['parameter']);

            if (!empty($acceptParameters)) {
                $parameters = array_intersect_key($parameters, array_flip($acceptParameters));
            }
        }

        // check required
        if (!empty($config['required'])) {
            $requiredParameters = explode(',', $config['required']);
            if (!empty($requiredParameters) && count($requiredParameters) !== count(array_intersect_key(array_flip($requiredParameters), $parameters))) {
                $keys = array_diff($requiredParameters, array_keys($parameters));
                throw new HttpInvalidParamException(implode(', ', $keys) . ' parameters missed');
            }
        }

        $baseUri = $config['base_uri'];
        $uri     = $config['uri'];
        $headers = [];

        // hook base uri
        if (method_exists(get_called_class(), 'rebuildBaseUri') === true) {
            $baseUri = $config['base_uri'] = static::rebuildBaseUri($config['base_uri'], $parameters, $config);
        }

        // hook uri
        if (method_exists(get_called_class(), 'rebuildUri') === true) {
            $uri = $config['uri'] = static::rebuildUri($config['uri'], $parameters, $config, $key);
        }

        // hook parameters
        if (method_exists(get_called_class(), 'rebuildParameters') === true) {
            $parameters = static::rebuildParameters($parameters, $uri, $key, $config);
        }

        // hook header
        if (method_exists(get_called_class(), 'getRequestHeaders') === true) {
            $headers = static::getRequestHeaders($parameters, $config);
        }

        // create http client
        $httpClient = \MobileApi\Util\Http\Client::getInstance();

        $option = [
            'base_uri'        => $baseUri,
            'timeout'         => $config['request_timeout'],
            'connect_timeout' => $config['connect_timeout'],
            'headers'         => $headers,
        ];

        // append request url
        $requestUrl = $requestFullUrl = $baseUri . '/' . $uri;

        /* @var TransferStats $statsInfo */
        $statsInfo          = [];
        $option['on_stats'] = function (TransferStats $stats) use (&$statsInfo) {
            $statsInfo = $stats;
        };

        if ($config['method'] === 'get') {
            $dash = strpos($requestUrl, '?') ? '&' : '?';
            //get method needs full url
            $requestFullUrl = empty($parameters) ? $requestUrl : ($requestUrl . $dash . \GuzzleHttp\Psr7\build_query($parameters));
        } else {
            $isMultipart = false;
            if (is_array($parameters)) {
                foreach ($parameters as $key => $parameter) {
                    if (!$isMultipart && $parameter instanceof \CURLFile) {
                        $isMultipart = true;
                        break;
                    }
                }
            }
            if ($isMultipart) {
                foreach ($parameters as $key => $parameter) {
                    if ($parameter instanceof \CURLFile) {

                        $option['multipart'][] = [
                            'name'     => $key,
                            'contents' => fopen($parameter->getFilename(), 'r'),
                            'filename' => $parameter->getPostFilename(),
                        ];
                    } else {
                        $option['multipart'][] = [
                            'name'     => $key,
                            'contents' => $parameter,
                        ];
                    }
                }
            } else {
                $option['body'] = is_string($parameters) ? $parameters : http_build_query($parameters);
                if (empty($option['headers']['Content-Type'])) {
                    $option['headers']['Content-Type'] = 'application/x-www-form-urlencoded';
                }
            }
        }

        $afterResponseFunc = static::getCallbackFunction($requestUrl, $parameters, $option, $statsInfo, $config, $async);

        if ($async === true) {
            $promise = $httpClient->requestAsync($config['method'], $requestFullUrl, $option);

            return new AsyncHandler($promise, $afterResponseFunc);
        }

        //async method has no retry.
        $requestRetryCount = $config['request_retry_count'];

        START_REQUEST:
        $response              = null;
        $exception             = null;
        $httpResponseException = null;
        //to request
        try {
            $response = $httpClient->request($config['method'], $requestFullUrl, $option);
        } catch (\Exception $httpResponseException) {
        }

        //to parse response and log
        try {
            $return = $afterResponseFunc($response, $httpResponseException);
        } catch (\Exception $exception) {
        }

        //to retry
        if (!empty($httpResponseException) && $requestRetryCount-- > 0) {
            goto START_REQUEST;
        }

        if (empty($return) && !empty($exception)) {
            throw $exception;
        }

        return $return;
    }

    /**
     * @param string        $requestUrl
     * @param array         $parameters
     * @param array         $guzzleOption
     * @param TransferStats $statsInfo
     * @param array         $config
     * @param bool          $async
     *
     * @return \Closure
     */
    protected static function getCallbackFunction($requestUrl, $parameters, $guzzleOption, &$statsInfo, $config, $async)
    {
        return function ($responseObj, \Exception $exception = null) use ($requestUrl, $parameters, $guzzleOption, &$statsInfo, $config, $async) {
            // 改写日志url
            $logUrl = $requestUrl;
            if (strpos($requestUrl, 'api/http2dubbo/invoke') !== false || $config['is_dubbo_backend']) {
                parse_str($parameters, $decodeParameters);
                $className = $decodeParameters['className'] ?? '';
                $method    = $decodeParameters['method'] ?? '';
                $logUrl    = $requestUrl . '?' . 'className=' . $className . '&method=' . $method;
            }

            // 是否忽略请求成功日志
            $isIgnoreSuccessLog = !empty($config['log_success_off']);

            $baseLogInfo = [
                'url'                 => $logUrl,
                'data'                => $parameters,
                'headers'             => $guzzleOption['headers'],
                'method'              => $config['method'],
                'namelookup_time'     => round($statsInfo->getHandlerStat('namelookup_time') * 1000),
                'connect_time'        => round($statsInfo->getHandlerStat('connect_time') * 1000),
                'pre_transfer_time'   => round($statsInfo->getHandlerStat('pretransfer_time') * 1000),
                'start_transfer_time' => round($statsInfo->getHandlerStat('starttransfer_time') * 1000),
                'total_time'          => round($statsInfo->getHandlerStat('total_time') * 1000),
                'is_async'            => $async ? 1 : 0,
            ];

            try {
                if ($exception instanceof \GuzzleHttp\Exception\ClientException) {
                    $responseObj = $exception->getResponse();
                } elseif ($exception) {
                    throw new HttpRuntimeException($exception->getMessage(), Message::getCode('system_error'));
                } elseif (empty($responseObj) || !($responseObj instanceof ResponseInterface)) {
                    throw new HttpResponseException('response error');
                }

                /* @var ResponseInterface $responseObj */
                $response = (string)$responseObj->getBody();

                // hook response
                if (method_exists(get_called_class(), 'rebuildResponse') === true) {
                    $response = static::rebuildResponse($response, $config);
                } else {
                    if (!is_array($response)) {
                        $response = json_decode($response, true);
                    }

                    if (!isset($response[$config['data_key']])) {
                        $response[$config['data_key']] = [];
                    }

                    if (!isset($response[$config['code_key']])) {
                        $response[$config['code_key']] = -1;
                    }

                    if (!isset($response[$config['message_key']])) {
                        $response[$config['message_key']] = '';
                    }
                }

                if ($config['is_restful']) {
                    $data = $response;
                    goto SUCCESS;
                }

                // check response is empty
                if (empty($response)) {
                    throw new HttpResponseException('response empty');
                }

                // check error code
                if (strval($response[$config['code_key']]) !== strval($config['success_code'])) {
                    throw new HttpResponseException($response[$config['message_key']], $response[$config['code_key']], $response);
                }

                // check result data key
                if (!array_key_exists($config['data_key'], $response)) {
                    throw new HttpResponseException('data not exists', 0, $response);
                }
            } catch (\Exception $e) {
                $httpError = $responseObj instanceof ResponseInterface ? $responseObj->getReasonPhrase() : $e->getMessage();
                $httpCode  = $responseObj instanceof ResponseInterface ? $responseObj->getStatusCode() : 0;
                $response  = $responseObj instanceof ResponseInterface ? (string)$responseObj->getBody() : '';

                if (in_array($e->getCode(), explode(',', $config['exclude_code'] ?? ''))) {
                    if (!$isIgnoreSuccessLog) {
                        Log::apiInfo('successed', array_merge($baseLogInfo,
                            [
                                'http_code' => $httpCode,
                                'response'  => json_decode($response, true) ?: $response,
                            ]
                        ));
                    }
                } else {
                    Log::apiError(intval($httpCode) === 200 ? 'warning' : 'failed', array_merge($baseLogInfo,
                        [
                            'http_error' => $httpError,
                            'http_code'  => $httpCode,
                            'response'   => json_decode($response, true) ?: $response,
                        ]
                    ));
                }

                if ($e instanceof HttpResponseException) {
                    throw $e;
                }

                throw new HttpResponseException(Message::getText('system_error'), Message::getCode('system_error'), $response);
            }

            // get response data
            $data = $response[$config['data_key']];

            // append default key and value
            if (!empty($config['default'])) {
                foreach (explode(',', $config['default']) as $default) {
                    list($key, $value) = explode(':', $default);

                    if (!isset($data[$key])) {
                        $data[$key] = $value;
                    }
                }
            }

            // replace data keys alias
            if (!empty($config['alias'])) {
                foreach (explode(',', $config['alias']) as $alias) {
                    list($source, $destination) = explode(':', $alias);

                    if (isset($data[$source])) {
                        $data[$destination] = $data[$source];

                        unset($data[$source]);
                    }
                }
            }

            SUCCESS:
            if (!$isIgnoreSuccessLog) {
                Log::apiInfo('successed', array_merge($baseLogInfo,
                    [
                        'http_code' => $responseObj->getStatusCode(),
                        'response'  => json_decode((string)$responseObj->getBody(), true) ?: (string)$responseObj->getBody(),
                    ]
                ));
            }

            return $data;
        };
    }
}
