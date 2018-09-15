<?php

namespace MobileApi\Util;

use MobileApi\Util\Redis\Manager;

class Cache
{

    /**
     * Variable  redis
     *
     * @author   wangxuan
     * @var      Manager|null
     */
    private $redis = null;

    /**
     * Variable  lockKeyPrefix
     *
     * @author   wangxuan
     * @var      string
     */
    private $lockKeyPrefix = 'lock_';

    /**
     * Variable  lockExpireTime
     *
     * @author   wangxuan
     * @var      int
     */
    private $lockExpireTime = 5;

    /**
     * Variable  lockWaitTime
     *
     * @author   wangxuan
     * @var      float
     */
    private $lockWaitTime = 0.5;

    /**
     * Variable  lockWaitCount
     *
     * @author   wangxuan
     * @var      int
     */
    private $lockWaitCount = 2;

    /**
     * Method __construct
     *
     * @author suchong
     *
     * @param string $connection
     */
    public function __construct($connection = 'default')
    {
        $this->redis = new Manager($connection);
    }

    /**
     * Method  remember
     * Get an item from the cache, or store the default value.
     *
     * @author suchong
     *
     * @param string        $key
     * @param int           $timeout
     * @param \Closure|null $callback
     * @param bool          $isUseExpireData
     * @param bool          $isOnWriteClient
     *
     * @throws \Exception
     * @return array|bool|mixed
     */
    public function remember($key, $timeout = 60, \Closure $callback = null, $isUseExpireData = false, $isOnWriteClient = false)
    {
        $cache = $this->get($key, $isOnWriteClient);

        // 兼容老版本
        if (isset($cache['data'], $cache['time'], $cache['timeout'])) {
            if (Server::get('request_time') - $cache['time'] <= $timeout) {
                return $cache['data'];
            }
        } else {
            if ($cache !== null && $cache !== false) {
                return $cache;
            }
        }

        if ($callback === null) {
            return false;
        }

        try {
            $result = $callback();

            if ($result === null || $result === false) {
                if (isset($cache['data']) && $isUseExpireData === true) {
                    return $cache['data'];
                } else {
                    return false;
                }
            }

            $cache = [
                'data'    => is_numeric($result) ? (string)$result : $result,
                'time'    => Server::get('request_time'),
                'timeout' => (int)$timeout,
            ];

            $this->set($key, $cache, $timeout * 3);

            return $result;
        } catch (\Exception $exception) {
            if (isset($cache['data']) && $isUseExpireData === true) {
                return $cache['data'];
            } else {
                throw $exception;
            }
        }
    }

    /**
     * Method  remeberForLock
     *
     * @author wangxuan
     *
     * @param string        $key
     * @param int           $timeout
     * @param \Closure|null $callback
     * @param bool          $isUseExpireData
     * @param bool          $isOnWriteClient
     *
     * @throws \Exception
     * @return bool|mixed
     */
    public function rememberForLock($key, $timeout = 60, \Closure $callback = null, $isUseExpireData = false, $isOnWriteClient = false)
    {
        $cache = $this->get($key, $isOnWriteClient);

        // 兼容老版本
        if (isset($cache['data'], $cache['time'], $cache['timeout'])) {
            if (Server::get('request_time') - $cache['time'] <= $timeout) {
                return $cache['data'];
            }
        } else {
            if ($cache !== null && $cache !== false) {
                return $cache;
            }
        }

        if ($callback === null) {
            return false;
        }

        $isLocking = false;

        $lockKey = $this->getLockKeyPrefix() . $key;

        try {
            $isLocking = $this->set($lockKey, 'lock', [
                'nx',
                'ex' => $this->getLockExpireTime(),
            ]);

            // check lock
            if ($isLocking === true) {
                // get data
                $result = $callback();

                // callback fail
                if ($result === null || $result === false) {
                    $this->del($lockKey);

                    // check use expire data
                    if (isset($cache['data']) && $isUseExpireData === true) {
                        return $cache['data'];
                    } else {
                        return false;
                    }
                }

                // set cache
                $cache = [
                    'data'    => is_numeric($result) ? (string)$result : $result,
                    'time'    => Server::get('request_time'),
                    'timeout' => (int)$timeout,
                ];

                $this->set($key, $cache, $timeout * 3);

                // unlock
                $this->del($lockKey);

                return $cache['data'];
            } else {
                // wait lock
                $count = $this->getLockWaitCount();

                while ($count-- > 0) {
                    $isLocking = $this->set($lockKey, 'lock', [
                        'nx',
                        'ex' => $this->getLockExpireTime(),
                    ]);

                    // check lock
                    if ($isLocking === false) {
                        usleep($this->getLockWaitTime() * 1000000);
                        continue;
                    }

                    // unlock
                    $this->del($lockKey);

                    // get previous cache
                    $result = $this->get($key);

                    if (isset($result['data'], $result['time'], $cache['time']) && $result['time'] > $cache['time']) {
                        return $result['data'];
                    } else {
                        break;
                    }
                }

                // check use expire data
                if (isset($cache['data']) && $isUseExpireData === true) {
                    return $cache['data'];
                } else {
                    return false;
                }
            }
        } catch (\Exception $exception) {
            // check lock
            if ($isLocking === true) {
                $this->del($lockKey);
            }

            if (isset($cache['data']) && $isUseExpireData === true) {
                return $cache['data'];
            } else {
                throw $exception;
            }
        }
    }

    /**
     * Method  get
     *
     * @author suchong
     *
     * @param string $key
     * @param bool   $isOnWriteClient
     *
     * @return mixed
     */
    public function get($key, $isOnWriteClient = false)
    {
        if ($isOnWriteClient === false) {
            $client = $this->redis->getReadClient();
        } else {
            $client = $this->redis->getWriteClient();
        }

        if (empty($client)) {
            return false;
        }

        $value = $client->get($key);

        return is_numeric($value) ? (int)$value : json_decode($value, true);
    }

    /**
     * Method  set
     *
     * @author suchong
     *
     * @param string    $key
     * @param string    $value
     * @param int|array $option
     *
     * @return mixed
     */
    public function set($key, $value, $option = null)
    {
        $client = $this->redis->getWriteClient();

        if (empty($client)) {
            return false;
        }

        $value = is_numeric($value) ? (string)$value : json_encode($value);

        if ($option === null) {
            return $client->set($key, $value);
        } else {
            return $client->set($key, $value, $option);
        }
    }

    /**
     * Method  __call
     *
     * @author suchong
     *
     * @param string $name
     * @param array  $arguments
     *
     * @return mixed
     */
    public function __call($name, array $arguments)
    {
        return $this->redis->__call($name, $arguments);
    }

    /**
     * Method  getLockKeyPrefix
     *
     * @author wangxuan
     * @return string
     */
    public function getLockKeyPrefix()
    {
        return $this->lockKeyPrefix;
    }

    /**
     * Method  setLockKeyPrefix
     *
     * @author wangxuan
     *
     * @param string $lockKeyPrefix
     */
    public function setLockKeyPrefix($lockKeyPrefix = 'lock_')
    {
        $this->lockKeyPrefix = $lockKeyPrefix;
    }

    /**
     * Method  getLockExpireTime
     *
     * @author wangxuan
     * @return int
     */
    public function getLockExpireTime()
    {
        return $this->lockExpireTime;
    }

    /**
     * Method  setLockExpireTime
     *
     * @author wangxuan
     *
     * @param int $lockExpireTime
     */
    public function setLockExpireTime($lockExpireTime)
    {
        $this->lockExpireTime = $lockExpireTime;
    }

    /**
     * Method  getLockWaitTime
     *
     * @author wangxuan
     * @return float
     */
    public function getLockWaitTime()
    {
        return $this->lockWaitTime;
    }

    /**
     * Method  setLockWaitTime
     *
     * @author wangxuan
     *
     * @param float $lockWaitTime
     */
    public function setLockWaitTime($lockWaitTime)
    {
        $this->lockWaitTime = $lockWaitTime;
    }

    /**
     * Method  getLockWaitCount
     *
     * @author wangxuan
     * @return int
     */
    public function getLockWaitCount()
    {
        return $this->lockWaitCount;
    }

    /**
     * Method  setLockWaitCount
     *
     * @author wangxuan
     *
     * @param int $lockWaitCount
     */
    public function setLockWaitCount($lockWaitCount)
    {
        $this->lockWaitCount = $lockWaitCount;
    }

    /**
     * 删除缓存，支持模糊、精确删除
     *
     * @param string|array $keys
     *
     * @return bool
     */
    public function del($keys)
    {
        if (empty($keys)) {
            return true;
        }

        if (is_string($keys)) {
            $keys = (array)$keys;
        }

        $distinctKeys = [];
        $regularKeys  = [];

        foreach ($keys as $key) {
            if (strpos($key, '_%s') !== false) {
                // 处理 cache key 模板
                $regularKeys[] = substr($key, 0, strpos($key, '%s')) . '*';
            } elseif (strpos($key, '*') !== false) {
                // 处理通配符 key
                $regularKeys[] = $key;
            } else {
                // 固定 key
                $distinctKeys [] = $key;
            }
        }

        // 处理通配符 key 查询结果
        if (!empty($regularKeys)) {
            foreach ($regularKeys as $regularKey) {
                $iterator = null;
                do {
                    $scanResult = $this->redis->getConnection()->scan($iterator, $regularKey, 100);
                    if (!empty($scanResult)) {
                        $distinctKeys = array_merge($distinctKeys, $scanResult);
                    }
                } while ($iterator > 0);
            }
        }

        $this->redis->del(array_unique($distinctKeys));

        return true;
    }
}
