<?php

namespace MobileApi\Util;

use Illuminate\Database\Capsule\Manager as Capsule;

class DB extends Capsule
{
    protected static $_instance;

    public function __construct($container = null)
    {
        parent::__construct($container);
        self::_initDatabase();
    }
    /**
     * Method  getInstance
     *
     * @author wangxuan
     * @static
     * @return static
     */
    public static function getInstance()
    {
        if (static::$_instance instanceof static) {
            return static::$_instance;
        }

        return static::$_instance = new static;
    }

    /**
     * Method  initDatabases
     *
     * @author zhangyuchong wangxuan
     * @static
     */
    public static function _initDatabase()
    {
        $databaseConfigs = Config::get('database.mysql');

        if (empty($databaseConfigs)) {
            throw new \InvalidArgumentException('database not configured.');
        }

        $capsule = new Capsule();

        foreach ($databaseConfigs as $databaseKey => $databaseConfig) {
            if (isset($databaseConfig['options'])) {
                $options = [];

                foreach ($databaseConfig['options'] as $key => $value) {
                    $constantName = 'PDO::' . strtoupper($key);

                    if (defined($constantName)) {
                        $key = constant($constantName);
                    }

                    if (strtolower($value) === 'true') {
                        $value = true;
                    } elseif (strtolower($value) === 'false') {
                        $value = false;
                    }

                    $options[$key] = $value;
                }

                $databaseConfig['options'] = $options;
            }

            $capsule->addConnection($databaseConfig, $databaseKey);
        }

        $capsule->setAsGlobal();

        $capsule->bootEloquent();
    }
}