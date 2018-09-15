<?php

namespace MobileApi\Util\Redis;

use MobileApi\Util\Log;

class Client
{

    private $_connectionName;

    private $_host;

    private $_port;

    private $_password;

    private $_database;

    private $_timeout;

    private $_enable = false;

    private static $_instances = [];

    /**
     * Method  __construct
     *
     * @author wangxuan
     *
     * @param string $connectionName
     * @param array  $config
     */
    public function __construct($connectionName, $config)
    {
        if (!empty($config['enable'])) {
            $this->_connectionName = $connectionName;
            $this->_host           = !empty($config['host']) ? $config['host'] : null;
            $this->_port           = !empty($config['port']) ? $config['port'] : null;
            $this->_password       = !empty($config['password']) ? $config['password'] : null;
            $this->_database       = !empty($config['database']) ? $config['database'] : 0;
            $this->_timeout        = !empty($config['timeout']) ? $config['timeout'] : 0;
            $this->_enable         = true;
        }
    }

    /**
     * Method  __construct
     *
     * @author wangxuan
     */
    public function __destruct()
    {
        foreach (self::$_instances as $key => $instance) {
            try {
                if (!empty($instance) && $instance instanceof self) {
                    $instance->close();

                    unset(self::$_instances[$key]);
                }
            } catch (\RedisException $exception) {
                Log::errorInfo('RedisException:' . $exception->getMessage());
            }
        }
    }

    /**
     * Method  isEnable
     *
     * @author wangxuan
     * @return bool
     */
    public function isEnable()
    {
        return $this->_enable;
    }

    /**
     * Method  getConnection
     *
     * @author wangxuan
     * @return bool
     */
    public function getConnection()
    {
        if ($this->_enable !== true) {
            return false;
        }

        if (!isset(self::$_instances[$this->_connectionName])) {
            try {
                $redis = new \Redis();

                $result = $redis->connect($this->_host, $this->_port, $this->_timeout);

                if ($result === false) {
                    return false;
                }

                if (!empty($this->_password)) {
                    $result = $redis->auth($this->_password);

                    if ($result === false) {
                        return false;
                    }
                }

                if (!empty($this->_database)) {
                    $result = $redis->select($this->_database);

                    if ($result === false) {
                        return false;
                    }
                }

                self::$_instances[$this->_connectionName] = $redis;
            } catch (\RedisException $exception) {
                Log::errorInfo('RedisException:' . $exception->getMessage());

                return false;
            }
        }

        return self::$_instances[$this->_connectionName];
    }

    /**
     * Method  closeConnection
     *
     * @author wangxuan
     * @return bool
     */
    public function closeConnection()
    {
        if ($this->_enable !== true) {
            return false;
        }

        if (!isset(self::$_instances[$this->_connectionName])) {
            return false;
        }

        try {
            $connection = self::$_instances[$this->_connectionName];

            unset(self::$_instances[$this->_connectionName]);

            return $connection->close();
        } catch (\RedisException $exception) {
            Log::errorInfo('RedisException:' . $exception->getMessage());

            return false;
        }

    }

    /**
     * Method  __call
     *
     * @author wangxuan
     *
     * @param string $name
     * @param array  $arguments
     *
     * @return mixed
     */
    public function __call($name, array $arguments)
    {
        if ($this->_enable !== true) {
            return false;
        }

        if ($name !== 'close') {
            try {
                $connection = $this->getConnection();

                if (!empty($connection)) {
                    return call_user_func_array([
                        $connection,
                        $name,
                    ], $arguments);
                }

                return false;
            } catch (\RedisException $exception) {
                Log::errorInfo('RedisException:' . $exception->getMessage());

                return false;
            }
        } else {
            return $this->closeConnection();
        }
    }

}