<?php

namespace SoftFailer\Storage;

use Exception;
use SoftFailer\Exception\LockWaitTimeoutException;
use SoftFailer\StorageData;

class Memcached implements Storage {
    /** @var int */
    private $sleepTimeMS = 10;

    /** @var \Memcached */
    private $memcached;

    /** @var string */
    private $key;

    /** @var string */
    private $lockKey;

    /** @var int */
    private $timeoutMS;

    /** @var bool */
    private $locked = false;

    /**
     * Memcached constructor.
     *
     * @param \Memcached $memcached
     * @param string    $key
     * @param int       $timeoutMS
     */
    public function __construct(\Memcached $memcached, string $key, int $timeoutMS) {
        $this->memcached = $memcached;
        $this->key = $key;
        $this->lockKey = $key . '_lock';
        $this->timeoutMS = $timeoutMS;
    }

    /**
     * @throws Exception
     */
    public function __destruct () {
        $this->unlock();
    }

    /**
     * @param int $sleepTimeMS
     *
     * @return Memcached
     */
    public function setSleepTimeMS(int $sleepTimeMS): Memcached {
        $this->sleepTimeMS = $sleepTimeMS;
        return $this;
    }

    /**
     * @param StorageData $storageData

     * @throws Exception
     */
    public function load(StorageData $storageData): void {
        $dataRaw = $this->memcached->get($this->key);
        if ($dataRaw === false) {
            if ($this->memcached->getResultCode() === \Memcached::RES_NOTFOUND) {
                // if key does not yet exist, just don't load
                return;
            }
            else {
                throw new Exception("can't load key {$this->key} from memcached: " . $this->memcached->getResultMessage());
            }
        }

        $data = json_decode($dataRaw, true);
        if ($data === false) {
            throw new Exception("can't decode JSON: " . $dataRaw);
        }

        if (!isset($data['failPoints']) || !is_array($data['failPoints'])) {
            throw new Exception("no failPoints found: " . $dataRaw);
        }

        $storageData->addFromStrings($data['failPoints']);
    }

    /**
     * @param StorageData $storageData
     *
     * @throws Exception
     */
    public function save(StorageData $storageData): void {
        $data = [
            'failPoints' => $storageData->toStrings(),
        ];

        $dataRaw = json_encode($data);
        $result = $this->memcached->set($this->key, $dataRaw, 0);

        if (!$result) {
            throw new Exception("can't save data for key {$this->key}: " . $this->memcached->getResultMessage());
        }
    }

    /**
     * @throws LockWaitTimeoutException
     * @throws Exception
     */
    public function lock(): void {
        // set TTL to twice the length of the wait timeout to avoid permanent locking in case of unexcpected aborts
        $lockTTLSeconds = ceil($this->timeoutMS / 500);

        $timeStart = microtime(true);
        do {
            $result = $this->memcached->add($this->lockKey, $this->key, $lockTTLSeconds);
            if ($result === true) {
                $this->locked = true;
                return;
            }

            if ($this->memcached->getResultCode() !== \Memcached::RES_NOTSTORED) {
                throw new Exception("can't write lock key {$this->lockKey} to memcached: " . $this->memcached->getResultMessage());
            }

            usleep(1000 * $this->sleepTimeMS);
            $msPassed = intval((microtime(true) - $timeStart) * 1000);
        }
        while($msPassed < $this->timeoutMS);
        throw new LockWaitTimeoutException($this->lockKey, $msPassed);
    }

    /**
     *
     * @throws Exception
     */
    public function unlock(): void {
        if (!$this->locked) {
            return;
        }

        if (!$this->memcached->delete($this->lockKey)) {
            throw new Exception("can't delete lock key {$this->lockKey} from memcached: " . $this->memcached->getResultMessage());
        }

        $this->locked = false;
    }
}