<?php

namespace MobileApi\Util\Kafka;

use MobileApi\Util\Config;
use MobileApi\Util\Log;

class Consumer
{

    private $_brokers;

    private $_groupId;

    private $_sessionTimeout;

    private $_logLevel;

    private $_topic;

    private $_name;

    private $_timeout;

    private $_offsetReset;

    private $_commitEnable;

    private $_enable = false;

    private static $_instances = [];

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
            $this->_name           = $name;
            $this->_brokers        = !empty($config['brokers']) ? $config['brokers'] : null;
            $this->_groupId        = !empty($config['group_id']) ? $config['group_id'] : null;
            $this->_sessionTimeout = !empty($config['session_timeout']) ? $config['session_timeout'] : 10;
            $this->_logLevel       = !empty($config['log_level']) ? $config['log_level'] : null;
            $this->_topic          = !empty($config['topic']) ? $config['topic'] : null;
            $this->_timeout        = !empty($config['timeout']) ? $config['timeout'] : 1;
            $this->_offsetReset    = !empty($config['offset_reset']) ? $config['offset_reset'] : 'smallest';
            $this->_commitEnable   = !empty($config['commit_enable']) ? $config['commit_enable'] : true;
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
                    $instance->unsubscribe();

                    unset(self::$_instances[$key]);
                }
            } catch (\RdKafka\Exception $exception) {
                Log::errorInfo('KafkaException:' . $exception->getMessage());
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
     * Method  getConsumer
     *
     * @author wangxuan
     * @return bool
     */
    public function getConsumer()
    {
        if ($this->_enable !== true) {
            return false;
        }

        if (!isset(self::$_instances[$this->_name])) {
            try {
                $config = new \RdKafka\Conf();

                $config->setRebalanceCb(function (\RdKafka\KafkaConsumer $consumer, $errorCode, array $partitions = null) {
                    switch ($errorCode) {
                        case RD_KAFKA_RESP_ERR__ASSIGN_PARTITIONS:
                            $consumer->assign($partitions);

                            Log::traceInfo('Kafka partitions assign', $partitions);

                            break;
                        case RD_KAFKA_RESP_ERR__REVOKE_PARTITIONS:
                            $consumer->assign(null);

                            Log::traceInfo('Kafka partitions remove', $partitions);

                            break;
                        default:
                            Log::errorInfo('KafkaException:' . $errorCode);

                            throw new \Exception($errorCode);
                    }
                });

                $config->set('metadata.broker.list', $this->_brokers);

                $config->set('group.id', $this->_groupId);

                $config->set('session.timeout.ms', $this->_sessionTimeout * 1000);

                $topicConfig = new \RdKafka\TopicConf();

                $topicConfig->set('auto.offset.reset', $this->_offsetReset);

                $topicConfig->set('auto.commit.enable', $this->_commitEnable);

                $config->setDefaultTopicConf($topicConfig);

                $consumer = new \RdKafka\KafkaConsumer($config);

                $consumer->subscribe([$this->_topic]);

                self::$_instances[$this->_name] = $consumer;
            } catch (\RdKafka\Exception $exception) {
                Log::errorInfo('KafkaException:' . $exception->getMessage());

                return false;
            }
        }

        return self::$_instances[$this->_name];
    }

    /**
     * Method  consume
     *
     * @author wangxuan
     *
     * @param int $timeout
     *
     * @throws \Exception
     * @return bool|mixed
     */
    public function consume($timeout = null)
    {
        if ($timeout === null) {
            $timeout = $this->_timeout;
        }

        $message = $this->getConsumer()->consume($timeout * 1000);

        switch ($message->err) {
            case RD_KAFKA_RESP_ERR_NO_ERROR:
                break;
            case RD_KAFKA_RESP_ERR__PARTITION_EOF:
                return false;
            case RD_KAFKA_RESP_ERR__TIMED_OUT:
                return false;
            default:
                throw new \Exception($message->errstr(), $message->err);
        }

        $payload = json_decode($message->payload, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            return $payload;
        } else {
            return $message->payload;
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
     * @throws \Exception
     * @return bool|mixed
     */
    public function __call($name, array $arguments)
    {
        if ($this->_enable !== true) {
            return false;
        }

        try {
            $consumer = $this->getConsumer();

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
