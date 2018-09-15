<?php

use MobileApi\Util\Log;
use MobileApi\Util\Message;
use MobileApi\Util\Network;
use MobileApi\Util\Env;

class Error_Controller extends Base_Controller
{

    public function errorAction()
    {
        $exception = $this->getRequest()->getException();
        if (in_array(Env::getEnvironment(), ['develop', 'test'])) {
            print($exception);exit();
        }
        switch ($exception->getCode()) {
            case Yaf\ERR\STARTUP_FAILED:
            case Yaf\ERR\CALL_FAILED:
            case Yaf\ERR\AUTOLOAD_FAILED:
            case Yaf\ERR\TYPE_ERROR:
                header(Network::getHttpStatusLine(500));

                $response = $this->response(null, Message::getCode('system_error'), Message::getText('system_error'));
                break;
            case Yaf\ERR\ROUTE_FAILED:
            case Yaf\ERR\DISPATCH_FAILED:
            case Yaf\ERR\NOTFOUND\MODULE:
            case Yaf\ERR\NOTFOUND\CONTROLLER:
            case Yaf\ERR\NOTFOUND\ACTION:
            case Yaf\ERR\NOTFOUND\VIEW:
                header(Network::getHttpStatusLine(404));

                $response = $this->response(null, Message::getCode('request_limited'), Message::getText('request_limited'));

                Log::error('Exception:' . $exception->getMessage(), [], 'error');

                break;
            default:
                if (!is_a($exception, 'PDOException')) {
                    Log::error('Exception:' . $exception->getMessage(), [], 'error');

                    $response = $this->response(null, $exception->getCode(), $exception->getMessage());
                } else {
                    Log::error('Exception:' . $exception->getMessage(), [], 'database');

                    $response = $this->response(null, Message::getCode('resource_database_error'), Message::getText('resource_database_error'));
                }
        }

        return $response;
    }
}
