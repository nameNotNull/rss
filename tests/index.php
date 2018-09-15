<?php
ini_set('yaf.name_separator', '_');

define('ROOT_PATH', dirname(__DIR__));

define('APPLICATION_PATH', ROOT_PATH . '/application');

(new Yaf\Application(ROOT_PATH . '/config/application.ini', 'phpunit'))->bootstrap();
