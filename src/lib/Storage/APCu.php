<?php

namespace SoftFailer\Storage;

use Exception;
use SoftFailer\Exception\LockWaitTimeoutException;
use SoftFailer\StorageData;

class APCu implements Storage {
    /** @var int */
    private $sleepTimeMS = 10;

    /** @var string */
    private $key;

    /** @var string */
    private $lockKey;

    /** @var int */
    private $timeoutMS;

    /** @var bool */
    private $locked = false;

    /**
     * APCu constructor.
     *
     * @param string    $key
     * @param int       $timeoutMS
     */
    public function __construct(string $key, int $timeoutMS) {
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
     * @return APCu
     */
    public function setSleepTimeMS(int $sleepTimeMS): APCu {
        $this->sleepTimeMS = $sleepTimeMS;
        return $this;
    }

    /**
     * @param StorageData $storageData

     * @throws Exception
     */
    public function load(StorageData $storageData): void {
        $dataRaw = apcu_fetch($this->key);
        if ($dataRaw === false) {
            // if key does not yet exist, just don't load
            return;
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
        $result = apcu_store($this->key, $dataRaw, 0);

        if (!$result) {
            throw new Exception("can't save data for key {$this->key}");
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
            $result = apcu_add($this->lockKey, $this->key, $lockTTLSeconds);
            if ($result === true) {
                $this->locked = true;
                return;
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

        if (!apcu_delete($this->lockKey)) {
            throw new Exception("can't delete lock key {$this->lockKey}");
        }

        $this->locked = false;
    }
}