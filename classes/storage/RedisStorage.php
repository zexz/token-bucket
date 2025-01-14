<?php

namespace bandwidthThrottle\tokenBucket\storage;

use bandwidthThrottle\tokenBucket\storage\scope\GlobalScope;
use bandwidthThrottle\tokenBucket\util\DoublePacker;
// use Predis\Client;
// use Predis\PredisException;
use malkusch\lock\mutex\PredisMutex;
use malkusch\lock\mutex\Mutex;
use Illuminate\Support\Facades\Redis;
use Exception;

/**
 * Redis based storage which uses Laravel Redis Helper.
 *
 * This storage is in the global scope.
 */
final class RedisStorage implements Storage, GlobalScope
{
    
    /**
     * @var Mutex The mutex.
     */
    private $mutex;
    
    /**
     * @var Client The redis API.
     */
    private $redis;
    
    /**
     * @var string The key.
     */
    private $key;
    
    /**
     * Sets the Redis API.
     *
     * @param string $name  The resource name.
     * @param Redis $redis Laravel Redis Helper.
     */
    public function __construct($name, $redis)
    {
        $this->key   = $name;
        $this->redis = $redis;
        $this->mutex = new PredisMutex([$redis], $name);
    }
    
    public function bootstrap($microtime)
    {
        $this->setMicrotime($microtime);
    }
    
    public function isBootstrapped()
    {
        try {
            return (bool) $this->redis::get($this->key);
        } catch (Exception $e) {
            throw new StorageException("Failed to check for key existence", 0, $e);
        }
    }
    
    public function remove()
    {
        try {
            if (!$this->redis::del($this->key)) {
                throw new StorageException("Failed to delete key");
            }
        } catch (Exception $e) {
            throw new StorageException("Failed to delete key", 0, $e);
        }
    }
    
    /**
     * @SuppressWarnings(PHPMD)
     */
    public function setMicrotime($microtime)
    {
        try {
            $data = DoublePacker::pack($microtime);
            if (!$this->redis::set($this->key, $data)) {
                throw new StorageException("Failed to store microtime");
            }
        } catch (Exception $e) {
            throw new StorageException("Failed to store microtime", 0, $e);
        }
    }

    /**
     * @SuppressWarnings(PHPMD)
     */
    public function getMicrotime()
    {
        try {
            $data = $this->redis::get($this->key);
            if ($data === false) {
                throw new StorageException("Failed to get microtime");
            }
            return DoublePacker::unpack($data);
        } catch (Exception $e) {
            throw new StorageException("Failed to get microtime", 0, $e);
        }
    }

    public function getMutex()
    {
        return $this->mutex;
    }

    public function letMicrotimeUnchanged()
    {
    }
}
