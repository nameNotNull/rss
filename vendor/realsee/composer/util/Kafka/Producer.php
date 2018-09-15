<?php

namespace MobileApi\Util\Kafka;

use MobileApi\Util\Config;
use MobileApi\Util\Log;

class Producer
{

    private $_brokers;

    private $_groupId;

    private $_logLevel;

    private $_topic;

    private $_name;

    private $_timeout;

    private $_offsetReset;

    private $_commitEnable;

    private $_enable = false;

    private static $_instances = [];

    private static $_producers = [];

    private static $_partitionCounts = [];

    /**
     * Method  __construct
     *
     * @author wangxuan
     *
     * @param string $name
     */
    public function __construct($name = 'default')
    {
        $config = Config::get('queue.kafka.' . $name);

        if (!empty($config) && !empty($config['enable'])) {
            $this->_name         = $name;
            $this->_brokers      = !empty($config['brokers']) ? $config['brokers'] : null;
            $this->_groupId      = !empty($config['group_id']) ? $config['group_id'] : null;
            $this->_logLevel     = !empty($config['log_level']) ? $config['log_level'] : null;
            $this->_topic        = !empty($config['topic']) ? $config['topic'] : null;
            $this->_timeout      = !empty($config['timeout']) ? $config['timeout'] : 1;
            $this->_offsetReset  = !empty($config['offset_reset']) ? $config['offset_reset'] : 'smallest';
            $this->_commitEnable = !empty($config['commit_enable']) ? $config['commit_enable'] : true;
            $this->_enable       = true;
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
                    unset(self::$_instances[$key]);
                }
            } catch (\RdKafka\Exception $exception) {
                Log::errorInfo('KafkaException:' . $exception->getMessage());
            }
        }
    }

    /**
     * Method  getProducer
     *
     * @author wangxuan
     * @return bool
     */
    public function getProducer()
    {
        if ($this->_enable !== true) {
            return false;
        }

        if (!isset(self::$_instances[$this->_name])) {
            try {
                $producer = new \RdKafka\Producer();

                $producer->setLogLevel($this->_logLevel);

                $producer->addBrokers($this->_brokers);

                self::$_producers[$this->_name] = $producer;

                self::$_instances[$this->_name] = $producer->newTopic($this->_topic);
            } catch (\RdKafka\Exception $exception) {
                Log::errorInfo('KafkaException:' . $exception->getMessage());

                return false;
            }
        }

        return self::$_instances[$this->_name];
    }

    /**
     * Method  produce
     *
     * @author wangxuan
     *
     * @param array|string $message
     * @param string       $key
     * @param string|null  $partitionHashValue
     *
     * @return bool
     */
    public function produce($message, $key = null, $partitionHashValue = null)
    {
        if ($this->_enable !== true) {
            return false;
        }

        if (is_array($message)) {
            $payload = json_encode($message, JSON_UNESCAPED_UNICODE);
        } else {
            $payload = $message;
        }

        $partition = RD_KAFKA_PARTITION_UA;

        if ($partitionHashValue !== null && is_numeric($partitionHashValue)) {
            $partitionCount = $this->getPartitionCount();
            if ($partitionCount !== 0) {
                $partition = $partitionHashValue % $partitionCount;
            }
        }

        if ($key === null) {
            $this->getProducer()->produce($partition, 0, $payload);
        } else {
            $this->getProducer()->produce($partition, 0, $payload, $key);
        }

        return true;
    }

    /**
     * Method  getPartitionCount
     *
     * @author wangxuan
     * @return int
     */
    public function getPartitionCount()
    {
        if (!isset(self::$_partitionCounts[$this->_name])) {
            $count = 0;

            $producerTopic = $this->getProducer();

            $metadata = self::$_producers[$this->_name]->getMetadata(false, $producerTopic, $this->_timeout * 1000);

            if (!empty($metadata)) {
                foreach ($metadata->getTopics() as $key => $topic) {
                    if (!empty($topic)) {
                        $partitions = $topic->getPartitions();

                        if (!empty($partitions)) {
                            $count = $partitions->count();
                            
                            if (!empty($count)) {
                                break;
                            }
                        }
                    }
                }
            }

            self::$_partitionCounts[$this->_name] = $count;
        }

        return self::$_partitionCounts[$this->_name];
    }

    /**
     * Method  __call
     *
     * @author wangxuan
     *
     * @param string $name
     * @param array  $arguments
     *
     * @throws \Exception
     * @return bool|mixed
     */
    public function __call($name, array $arguments)
    {
        if ($this->_enable !== true) {
            return false;
        }

        try {
            $consumer = $this->getProducer();

            if (!empty($consumer)) {
                return call_user_func_array([
                    $consumer,
                    $name,
                ], $arguments);
            }

            return false;
        } catch (\RdKafka\Exception $exception) {
            Log::errorInfo('KafkaException:' . $exception->getMessage());

            throw new \Exception($exception->getMessage(), $exception->getCode());
        }
    }

}
