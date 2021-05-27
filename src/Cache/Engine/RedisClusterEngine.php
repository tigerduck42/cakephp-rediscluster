<?php
namespace Riesenia\RedisCluster\Cache\Engine;

use Cake\Cache\Engine\RedisEngine;

/**
 * RedisCluster storage engine for cache.
 */
class RedisClusterEngine extends RedisEngine
{
    /**
     * Redis wrapper.
     *
     * @var \RedisCluster
     */
    protected $_Redis;

    /**
     * PHP redis extension version
     * @var string
     */
    protected $_redisExtensionVersion;

    /**
     * The default config used unless overridden by runtime configuration.
     *
     * - `duration` Specify how long items in this cache configuration last.
     * - `groups` List of groups or 'tags' associated to every key stored in this config.
     *    handy for deleting a complete group from cache.
     * - `persistent` Connect to the Redis server with a persistent connection
     * - `prefix` Prefix appended to all entries. Good for when you need to share a keyspace
     *    with either another cache config or another application.
     * - `probability` Probability of hitting a cache gc cleanup. Setting to 0 will disable
     *    cache::gc from ever being called automatically.
     * - `server` array of Redis server hosts.
     * - `password` Password for Redis cluster authorisation
     * - `timeout` timeout in seconds (float).
     * - `read_timeout` read timeout in seconds (float).
     *
     * @var array
     */
    protected $_defaultConfig = [
        'name' => 'cache',
        'duration' => 3600,
        'groups' => [],
        'persistent' => true,
        'prefix' => 'cake_',
        'probability' => 100,
        'server' => [],
        'password' => null,
        'timeout' => 2,
        'read_timeout' => 2,
        'failover' => 'none'
    ];

    /**
     * {@inheritdoc}
     */
    public function init(array $config = [])
    {
        if (!extension_loaded('redis')) {
            return false;
        } else {
            $this->_redisExtensionVersion = filter_var(phpversion('redis'), FILTER_SANITIZE_NUMBER_INT);
        }

        if (!class_exists('RedisCluster')) {
            return false;
        }

        parent::init($config);

        return $this->_connect();
    }

    /**
     * {@inheritdoc}
     */
    public function delete($key)
    {
        $key = $this->_key($key);

        return $this->_Redis->del($key) > 0;
    }

    /**
     * {@inheritdoc}
     */
    public function clear($check)
    {
        if ($check) {
            return true;
        }

        $result = true;

        foreach ($this->_Redis->_masters() as $m) {
            $iterator = null;

            do {
                $keys = $this->_Redis->scan($iterator, $m, $this->_config['prefix'] . '*');

                if ($keys === false) {
                    continue;
                }

                foreach ($keys as $key) {
                    if ($this->_Redis->del($key) < 1) {
                        $result = false;
                    }
                }
            } while ($iterator > 0);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    protected function _connect()
    {
        try {
            if (400 <= $this->_redisExtensionVersion) {
                $this->_Redis = new \RedisCluster($this->_config['name'], $this->_config['server'], $this->_config['timeout'], $this->_config['read_timeout'], $this->_config['persistent'], $this->_config['password']);
            } else {
                if (isset($this->_config['password']) && null !== $this->_config['password']) {
                    trigger_error("Password not supported prior phpredis prior 4.0.0", E_USER_WARNING);
                }
                $this->_Redis = new \RedisCluster($this->_config['name'], $this->_config['server'], $this->_config['timeout'], $this->_config['read_timeout'], $this->_config['persistent']);
            }
            switch ($this->_config['failover']) {
                case 'error':
                    $this->_Redis->setOption(\RedisCluster::OPT_SLAVE_FAILOVER, \RedisCluster::FAILOVER_ERROR);
                    break;

                case 'distribute':
                    $this->_Redis->setOption(\RedisCluster::OPT_SLAVE_FAILOVER, \RedisCluster::FAILOVER_DISTRIBUTE);
                    break;

                case 'slaves':
                    $this->_Redis->setOption(\RedisCluster::OPT_SLAVE_FAILOVER, \RedisCluster::FAILOVER_DISTRIBUTE_SLAVES);
                    break;

                default:
                    $this->_Redis->setOption(\RedisCluster::OPT_SLAVE_FAILOVER, \RedisCluster::FAILOVER_NONE);
            }
        } catch (\RedisClusterException $e) {
            return false;
        }

        return true;
    }
}
