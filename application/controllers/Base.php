<?php
use Utils\Request;
use MobileApi\Api\Base;
use MobileApi\Exception\Exception;
use MobileApi\Exception\Http\HttpInvalidParamException;
use MobileApi\Exception\Http\HttpResponseException;
use MobileApi\Exception\Http\HttpUrlException;
use MobileApi\Exception\Http\RequestParamException;
use MobileApi\Util\Config;
use MobileApi\Util\Log;
use MobileApi\Util\Message;
use MobileApi\Util\Server;
use MobileApi\Util\Validator;
use Yaf\Controller_Abstract;
use Yaf\Registry;

/**
 * Class     Base_Controller
 *
 * @author   wangxuan
 */
class Base_Controller extends Controller_Abstract
{

    /**
     * Variable  idKey
     *
     * @author   wangxuan
     * @static
     * @var      string
     */
    protected static $idKey = 'request_id';

    /**
     * Variable  dataKey
     *
     * @author   wangxuan
     * @static
     * @var      string
     */
    protected static $dataKey = 'data';

    /**
     * Variable  codeKey
     *
     * @author   wangxuan
     * @static
     * @var      string
     */
    protected static $codeKey = 'code';

    /**
     * Variable  messageKey
     *
     * @author   wangxuan
     * @static
     * @var      string
     */
    protected static $messageKey = 'status';

    /**
     * Variable  successCode
     *
     * @author   wangxuan
     * @static
     * @var      int
     */
    protected static $successCode = 0;

    /**
     * Method  init
     *
     * @author wangxuan
     */
    public function init()
    {
    }

    /**
     * Method  indexAction
     *
     * @author wangxuan
     */
    public function indexAction()
    {
        try {
            // Request::init();

            Request::setController($this);

            Log::setRequestHandler(Request::class);
            Base::setRequestHandler(Request::class);

            $calledClass = get_called_class();

            if (method_exists($calledClass, 'autoValidation')) {
                static::autoValidation();
            }

            if (method_exists($calledClass, 'validateLogin')) {
                static::validateLogin();
            }

            if (method_exists($calledClass, 'beforeProcess')) {
                static::beforeProcess();
            }

            $response = static::process();

            if (method_exists($calledClass, 'afterProcess')) {
                static::afterProcess();
            }

            switch (gettype($response)) {
                case 'array':
                    static::response($response);
                    break;
                case 'string':
                    static::response(null, null, $response);
                    break;
                case 'integer':
                case 'double':
                    static::response(null, null, (string)$response);
                    break;
                default:
                    if ($response === true) {
                        static::success();
                    } else {
                        throw new Exception('system_error');
                    }
            }

        } catch (RequestParamException $exception) {
            Log::error('Exception:' . $exception->getMessage(), [
                'line'  => $exception->getLine(),
                'file'  => $exception->getFile(),
                'trace' => $exception->getTraceAsString(),
            ], 'error');

            static::response(null, Message::getCode('parameters_error'), ($exception->getMessage() !== '') ? $exception->getMessage() : Message::getText('parameters_error'));
        } catch (HttpUrlException $exception) {
            Log::error('Exception:' . $exception->getMessage(), [
                'line'  => $exception->getLine(),
                'file'  => $exception->getFile(),
                'trace' => $exception->getTraceAsString(),
            ], 'error');

            static::response(null, ($exception->getCode() !== 0) ? $exception->getCode() : Message::getCode('system_error'), ($exception->getMessage() !== '') ? $exception->getMessage() : Message::getText('system_error'));
        } catch (HttpInvalidParamException $exception) {
            Log::error('Exception:' . $exception->getMessage(), [
                'line'  => $exception->getLine(),
                'file'  => $exception->getFile(),
                'trace' => $exception->getTraceAsString(),
            ], 'error');

            static::response(null, ($exception->getCode() !== 0) ? $exception->getCode() : Message::getCode('parameters_error'), ($exception->getMessage() !== '') ? $exception->getMessage() : Message::getText('parameters_error'));
        } catch (HttpResponseException $exception) {
            Log::error('Exception:' . $exception->getMessage(), [
                'line'  => $exception->getLine(),
                'file'  => $exception->getFile(),
                'trace' => $exception->getTraceAsString(),
            ], 'error');

            static::response(null, ($exception->getCode() !== 0) ? $exception->getCode() : Message::getCode('system_error'), ($exception->getMessage() !== '') ? $exception->getMessage() : Message::getText('system_error'));
        } catch (PDOException $exception) {
            Log::error('Exception:' . $exception->getMessage(), [
                'line'  => $exception->getLine(),
                'file'  => $exception->getFile(),
                'trace' => $exception->getTraceAsString(),
            ], 'error');

            static::response(null, Message::getCode('resource_database_error'), Message::getText('resource_database_error'));
        } catch (\Exception $exception) {
            Log::error('Exception:' . $exception->getMessage(), [
                'line'  => $exception->getLine(),
                'file'  => $exception->getFile(),
                'trace' => $exception->getTraceAsString(),
            ], 'error');

            static::response(null, $exception->getCode(), $exception->getMessage());
        } finally {
            Log::info('input', $_REQUEST, 'request');
        }
    }

    /**
     * Method  getQuery
     *
     * @param string $name
     * @param string $default
     *
     * @return mixed
     */
    public function getQuery($name = null, $default = null)
    {
        if ($name !== null) {
            if ($this->getRequest()->isCli() === false) {
                $value = $this->getRequest()->getQuery($name, null);
            } else {
                $value = isset($_GET[$name]) ? $_GET[$name] : null;
            }

            if ($value !== null) {
                return $value;
            } else {
                return $default;
            }
        } else {
            if ($this->getRequest()->isCli() === false) {
                $data = $this->getRequest()->getQuery();
            } else {
                $data = $_GET;
            }
            if ($data) {
                return $data;
            } else {
                return [];
            }
        }
    }

    /**
     * Method  getPost
     *
     * @param string $name
     * @param string $default
     *
     * @return mixed
     */
    public function getPost($name = null, $default = null)
    {
        if ($name !== null) {
            if ($this->getRequest()->isCli() === false) {
                $value = $this->getRequest()->getPost($name, null);
            } else {
                $value = isset($_POST[$name]) ? $_POST[$name] : null;
            }

            if ($value !== null) {
                return $value;
            } else {
                return $default;
            }
        } else {
            if ($this->getRequest()->isCli() === false) {
                $data = $this->getRequest()->getPost();
            } else {
                $data = $_POST;
            }
            if ($data) {
                return $data;
            } else {
                if ($data = file_get_contents('php://input')) {
                    return !empty($data) ? json_decode($data, true) : null;
                } else {
                    return [];
                }
            }
        }
    }

    /**
     * Method  setParam
     *
     * @param string $name
     * @param string $value
     *
     * @return mixed
     */
    public function setParam($name, $value = null)
    {
        if (is_string($name)) {
            return $this->getRequest()->setParam($name, $value);
        } elseif (is_array($name)) {
            return $this->getRequest()->setParam($name);
        } else {
            return false;
        }
    }

    /**
     * Method  getParam
     *
     * @param string $name
     * @param string $default
     *
     * @return mixed
     */
    public function getParam($name = null, $default = null)
    {
        if ($name !== null) {
            $value = $this->getRequest()->getParam($name, null);

            if ($value !== null) {
                return $value;
            } else {
                return $default;
            }
        } else {
            return $this->getParams();
        }
    }


    /**
     * Method  getParams
     *
     * @return array
     */
    public function getParams()
    {
        return $this->getRequest()->getParams();
    }

    /**
     * Method  response
     *
     * @author wangxuan
     *
     * @param array  $data
     * @param string $code
     * @param string $message
     */
    public function response($data = [], $code = null, $message = null)
    {
        $requestId = Registry::get('request_id');

        $content = [
            static::$idKey      => $requestId,
            static::$codeKey    => !empty($code) ? (is_numeric($code) ? (int)$code : $code) : static::$successCode,
            static::$messageKey => $message !== null ? (!empty($message) ? $message : '') : '',
            static::$dataKey    => is_array($data) && count($data) > 0 ? $data : new \stdClass(),
            'cost'              => ceil((microtime(true) - Server::get('request_time_float')) * 1000),
        ];

        if (headers_sent() === false) {
            header('Content-Type: application/json; charset=utf-8;');

            header("Request-ID: {$requestId}");
        }

        $this->getResponse()->setBody(json_encode($content, JSON_UNESCAPED_UNICODE));

        Log::info('output', $content, 'request');
    }

    /**
     * Method  success
     *
     * @param string $message
     *
     * @return mixed
     */
    public function success($message = '操作成功')
    {
        return $this->response(null, null, $message);
    }


    /**
     * Method  jsonResponse
     *
     * @param array $data
     */
    public function jsonResponse($data = [])
    {
        $content = json_encode($data, JSON_UNESCAPED_UNICODE);

        $callback = $this->getRequest()->getQuery('callback', null);

        if (empty($callback)) {
            if (headers_sent() === false) {
                header('Content-Type: application/json; charset=utf-8;');
            }

            $this->getResponse()->setBody($content);
        } else {
            if (headers_sent() === false) {
                header('Content-Type: text/javascript; charset=utf-8;');
            }

            $this->getResponse()->setBody(addslashes($callback) . "({$content})");
        }
    }


    public function autoValidation()
    {
        // get config
        $module = strstr(get_called_class(), '_', true);

        $key = strtolower(preg_replace("/^{$module}_(.*)_Controller$/", '$1', get_called_class()));

        // $configKey = strtolower("validation.{$module}.{$key}");
        $configKey = strtolower("validation.{$module}_{$key}");

        $config = Config::get($configKey);

        // check enable
        if ((isset($config['enable']) && empty($config['enable'])) || (!isset($config['rule']) && !isset($config['method']))) {
            return true;
        }

        // check method or set default value 'get'
        if (!isset($config['method'])) {
            $config['method'] = 'get';
        }

        // check rule or set default value empty array
        if (!isset($config['rule'])) {
            $config['rule']   = [];
            $acceptParameters = [];
        } else {
            // get accept parameters
            $acceptParameters = array_fill_keys(array_keys($config['rule']), null);
        }

        $parameters = null;
        // strip invalid parameters
        if ($config['method'] === 'get') {
            if (strtolower($this->getRequest()->method) !== 'get') {
                throw new Exception('request_get_error');
            }
            $parameters = array_merge($acceptParameters, array_intersect_key($this->getQuery(), $acceptParameters));
        } elseif ($config['method'] === 'post') {
            if (strtolower($this->getRequest()->method) !== 'post') {
                throw new Exception('request_post_error');
            }
            $parameters = array_merge($acceptParameters, array_intersect_key(array_merge($this->getQuery(), $this->getPost()), $acceptParameters));
        } else {
            return true;
        }

        // check default
        if (!empty($config['default'])) {
            // fill default value
            foreach ($config['default'] as $key => $value) {
                if (!isset($parameters[$key])) {
                    $parameters[$key] = $value;
                }
            }
        }

        // check parameters
        if (empty($parameters)) {
            return true;
        }

        // convert custom message
        $messages = [];

        if (!empty($config['message'])) {
            foreach ($config['message'] as $key => $messagePairs) {
                foreach ($messagePairs as $rule => $message) {
                    $messages["{$key}.{$rule}"] = $message;
                }
            }
        }

        // validation
        $validator = Validator::make($parameters, $config['rule'], $messages);

        // check result
        if ($validator->fails()) {
            // throw exception
            throw new RequestParamException($validator->messages()->first());
        }

        // validation form data
        if (!empty($config['form_data_is_raw']) && isset($parameters['formData'])) {
            $formData = json_decode($parameters['formData'], true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new RequestParamException('formData is required');
            }

            $validator = Validator::make($formData, $config['raw_rule'], $messages);

            // check result
            if ($validator->fails()) {
                // throw exception
                throw new RequestParamException($validator->messages()->first());
            }

            $parameters['formData'] = $formData;
        }

        // set parameters to request params
        $this->getRequest()->setParam($parameters);

        return true;
    }

    /**
     * Method  validateLogin
     *
     * @author wangxuan
     */
    protected function validateLogin()
    {
        /*
        $requireLoginUris = Config::get('uri.require_login.uris', []);

        if (in_array(strtolower(Request::getUri()), $requireLoginUris)) {
            Request::getUser()->loginRequired();
        }
        */
    }

    /**
     * Method  getUserId
     *
     * @author wangxuan
     * @return int
     */
    protected function getUserId()
    {
        return (int)$this->getParam('user_id', 0);
    }


    /**
     * Method  filterStatus
     *
     * @author wangxuan
     *
     * @param array  $data
     * @param int    $status
     * @param string $statusKey
     *
     * @return array
     */
    protected function filterStatus(array $data, $status, $statusKey = 'status')
    {
        $list = [];

        foreach ($data ?? [] as $item) {
            if (isset($item[$statusKey]) && $item[$statusKey] === $status) {
                $list[] = $item;
            }
        }

        return $list;
    }

}
