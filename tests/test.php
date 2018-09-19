<?php
/**
 * 1、composer 安装Monolog日志扩展，安装phpunit单元测试扩展包
 * 2、引入autoload.php文件
 * 3、测试案例
 *
 *
**/
ini_set('yaf.name_separator', '_');
require_once __DIR__ . '/../vendor/autoload.php';
define("ROOT_PATH", dirname(__DIR__) . "/");
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use PHPUnit\Framework\TestCase;
use Yaf\Bootstrap_Abstract;
use Yaf\Dispatcher;
use Yaf\Loader;
use Yaf\Registry;
define('APPLICATION_PATH', ROOT_PATH . '/application');
class YafTest extends TestCase
{
    public function testSearch()
    {
        $application = new Yaf\Application(ROOT_PATH . "/config/application.ini",'develop');
        $application->bootstrap();
        Yaf\Registry::set('application', $application);
        $data = Service_Test_Model::getInstance()->getListByUserId('123');
        $this->assertEquals(1,$data[0]['id']);
        $stub = $this->createMock(Service_Test_Model::class);
        $stub->method('getListByUserId')->willReturn(3);                     //2
        $this->assertEquals(3,$stub->getListByUserId(1));
    }
    public function Log()
    {
        // create a log channel
        $log = new Logger('Tester');
        $log->pushHandler(new StreamHandler(ROOT_PATH . 'storage/logs/app.log', Logger::WARNING));
        return $log;
    }
}
