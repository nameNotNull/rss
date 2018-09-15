<?php
ini_set('yaf.name_separator', '_');

define('ROOT_PATH', dirname(__DIR__));

define('APPLICATION_PATH', ROOT_PATH . '/application');

define('APPLICATION_ENVIRONMENT', (function () {
    $environment = ini_get('yaf.environ');

    if ($environment === 'product' && isset($_SERVER['IDC']) && $_SERVER['IDC'] === 'aws') {
        return 'product-aws';
    }

    return $environment;
})());

$application = new Yaf\Application(ROOT_PATH . '/config/application.ini', APPLICATION_ENVIRONMENT);

$dispatcher = $application->bootstrap()->getDispatcher();

if ($dispatcher->getRequest()->isCli()) {
    $dispatcher->dispatch(new Yaf\Request\Simple());
}
