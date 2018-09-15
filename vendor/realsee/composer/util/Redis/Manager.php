<?php

namespace MobileApi\Util\Redis;

use MobileApi\Util\Config;

class Manager
{

    private $_enable = false;

    private $_writeClient = null;

    private $_readClient = null;

    private $_readCommands = [
        'get',
        'mget',
        'getbit',
        'getrange',
        'hget',
        'hmget',
        'hgetall',
    ];

    /**
     * Method  __construct
     *
     * @author wangxuan
     *
     * @param string $connection
     */
    public function __construct($connection = 'default')
    {
        $config = Config::get('cache.redis.' . $connection);

        if (empty($config)) {
            return;
        }

        if (!empty($config) && !empty($config['enable'])) {
            if (!empty($config['write']) && !empty($config['read'])) {
                $this->_writeClient = new Client($connection . '_write', $config['write']);
                $this->_readClient  = new Client($connection . '_read', $config['read']);
            } else {
                $this->_writeClient = new Client($connection, $config);
            }
            $this->_enable = true;
        }
    }

    /**
     * Method  __construct
     *
     * @author wangxuan
     */
    public function __destruct()
    {
        if ($this->_writeClient !== null && $this->_writeClient->isEnable() !== false) {
            $this->_writeClient->closeConnection();
        }

        if ($this->_readClient !== null && $this->_readClient->isEnable() !== false) {
            $this->_readClient->closeConnection();
        }
    }

    /**
     * Method  getWriteClient
     *
     * @author wangxuan
     * @return bool|Client|null
     */
    public function getWriteClient()
    {
        if ($this->_writeClient !== null && $this->_writeClient->isEnable() !== false) {
            return $this->_writeClient;
        } else {
            return false;
        }
    }

    /**
     * Method  getReadClient
     *
     * @author wangxuan
     * @return bool|Client|null
     */
    public function getReadClient()
    {
        if ($this->_readClient !== null && $this->_readClient->isEnable() !== false) {
            return $this->_readClient;
        } elseif ($this->_writeClient !== null && $this->_writeClient->isEnable() !== false) {
            return $this->_writeClient;
        } else {
            return false;
        }
    }

    /**
     * Method  closeConnection
     *
     * @author wangxuan
     * @return bool
     */
    public function closeConnection()
    {
        if ($this->_writeClient !== null && $this->_writeClient->isEnable() !== false) {
            $this->_writeClient->closeConnection();
        }

        if ($this->_readClient !== null && $this->_readClient->isEnable() !== false) {
            $this->_readClient->closeConnection();
        }

        return true;
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

        if ($this->_readClient !== null && in_array($name, $this->_readCommands)) {
            $connection = $this->getReadClient();
        } else {
            $connection = $this->getWriteClient();
        }

        if (!empty($connection)) {
            return call_user_func_array([
                $connection,
                $name,
            ], $arguments);
        }

        return false;
    }

}