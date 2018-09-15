<?php

use MobileApi\Util\Config;
use Yaf\Plugin_Abstract;
use Yaf\Registry;
use Yaf\Request_Abstract;
use Yaf\Response_Abstract;

/**
 * Class     DefaultPlugin
 * 默认插件
 */
class Default_Plugin extends Plugin_Abstract
{

    public function routerStartup(Request_Abstract $request, Response_Abstract $response)
    {
    }

    public function routerShutdown(Request_Abstract $request, Response_Abstract $response)
    {
        // get request uri
        $requestUri = strtolower(ltrim($request->getRequestUri(), '/'));

        // get controller name
        $controllerName = $request->controller;

        // replace character '-'
        if (strpos($controllerName, '-') !== false) {
            $controllerName = str_replace('-', '_', $controllerName);
        }

        // get character '.' position
        $position = strpos($controllerName, '.');

        // cut controller name and suffix
        if ($position !== false) {
            Registry::set('request_suffix', substr($controllerName, $position + 1));

            // $requestUri = substr($requestUri, 0, strpos($requestUri, '.'));

            $controllerName = substr($controllerName, 0, $position);
        }

        // set request uri
        $request->setRequestUri($requestUri);

        Registry::set('request_uri', $requestUri);

        // convert controller name
        if (in_array($controllerName, Config::get('application.convert.controllers', []))) {
            if ($request->isGet() === true) {
                $controllerName .= 'ForGet';
            } elseif ($request->isPost() === true) {
                $controllerName .= 'ForPost';
            } else {
                if (!empty($_POST)) {
                    $controllerName .= 'ForPost';
                } else {
                    $controllerName .= 'ForGet';
                }
            }
        }

        // set controller name
        if ($controllerName !== $request->controller) {
            $request->setControllerName($controllerName);
        }
    }

    public function dispatchLoopStartup(Request_Abstract $request, Response_Abstract $response)
    {
    }

    public function preDispatch(Request_Abstract $request, Response_Abstract $response)
    {
    }

    public function postDispatch(Request_Abstract $request, Response_Abstract $response)
    {
    }

    public function dispatchLoopShutdown(Request_Abstract $request, Response_Abstract $response)
    {
    }

}
