<?php

use MobileApi\Util\Config;

class Dao_Base_Model extends \Illuminate\Database\Eloquent\Model
{

    const CREATED_AT = 'create_time';

    const UPDATED_AT = 'modify_time';

    const DELETED_AT = 'remove_time';

    public $incrementing = true;

    protected $hidden = [
        'create_time',
        'modify_time',
    ];

    protected static $instance;

    /**
     * Method __construct
     *
     * @author wangxuan
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        static::getConnection()->enableQueryLog();
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
        if (static::$instance instanceof static) {
            return static::$instance;
        }

        return static::$instance = new static;
    }

    /**
     * Method  getQueryLog
     *
     * @author wangxuan
     * @return array
     */
    public function getQueryLog()
    {
        return static::getConnection()->getQueryLog();
    }

    /**
     * Method  resolveConnection
     *
     * @author zhangyuchong
     * @static
     *
     * @param string|null $connection
     *
     * @return \Illuminate\Database\ConnectionInterface
     */
    public static function resolveConnection($connection = null)
    {
        return static::getConnectionResolver()->connection($connection);
    }

    /**
     * Method  getConnectionResolver
     *
     * @author zhangyuchong wangxuan
     * @static
     * @return \Illuminate\Database\ConnectionResolverInterface
     */
    public static function getConnectionResolver()
    {
        if (empty(static::$resolver)) {
            static::initDatabase();
        }

        return static::$resolver;
    }

    /**
     * Method  initDatabases
     *
     * @author zhangyuchong wangxuan
     * @static
     */
    private static function initDatabase()
    {
        $databaseConfigs = Config::get('database.mysql');

        if (empty($databaseConfigs)) {
            return;
        }

        $capsule = new \Illuminate\Database\Capsule\Manager();

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

    /**
     * Method  beginTransaction
     *
     * @author wangxuan
     */
    public function beginTransaction()
    {
        static::getConnection()->beginTransaction();
    }

    /**
     * Method  commit
     *
     * @author wangxuan
     */
    public function commit()
    {
        static::getConnection()->commit();
    }

    /**
     * Method  rollBack
     *
     * @author wangxuan
     */
    public function rollBack()
    {
        static::getConnection()->rollBack();
    }

    /**
     * Method  inTransaction
     *
     * @author wangxuan
     * @return bool
     */
    public function inTransaction()
    {
        return static::getConnection()->transactionLevel() !== 0;
    }

}
