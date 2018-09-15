<?php

use MobileApi\Util\Env;
use MobileApi\Util\Generator;
use MobileApi\Util\Server;
use Yaf\Bootstrap_Abstract;
use Yaf\Dispatcher;
use Yaf\Loader;
use Yaf\Registry;

class Bootstrap extends Bootstrap_Abstract
{

    public function _initView(Dispatcher $dispatcher)
    {
        Dispatcher::getInstance()->disableView();
    }

    public function _initLoader(Dispatcher $dispatcher)
    {
        Loader::import(ROOT_PATH . '/vendor/autoload.php');
    }

    public function _initLogLevel(Dispatcher $dispatcher)
    {
        // 设置不同环境的日志级别
        if (Env::isDevelop() === false) {
            error_reporting(E_ALL & ~(E_STRICT | E_NOTICE));
            ini_set('display_errors', false);
            ini_set('log_errors', true);
        } else {
            error_reporting(E_ALL);
            ini_set('display_errors', true);
            ini_set('log_errors', true);
        }
    }

    public function _initRequest(Dispatcher $dispatcher)
    {
        Registry::set('request_id', Server::get('http_uniqid', Generator::requestId()));
    }

    /*
    public function _initConfig(Dispatcher $dispatcher)
    {
        Registry::set('config', Application::app()->getConfig());
    }
    */

    public function _initPlugin(Dispatcher $dispatcher)
    {
        $dispatcher->registerPlugin(new Default_Plugin());
    }
}
