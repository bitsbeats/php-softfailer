<?php

namespace SoftFailer;

use Exception;
use DateTimeImmutable;
use DateTimeInterface;
use SoftFailer\Exception\HardFailLimitReachedException;
use SoftFailer\Exception\LockWaitTimeoutException;
use SoftFailer\Storage\Storage;

class SoftFailer {
    /** @var Storage */
    private $storage;

    /** @var StorageData */
    private $storageData;

    /** @var int */
    private $hardFailLimit;

    /** @var int */
    private $intervalSeconds;

    /**
     * SoftFailer constructor.
     *
     * @param Storage $storage
     * @param int     $hardFailLimit
     * @param int     $intervalSeconds
     *
     * @throws Exception
     */
    public function __construct(Storage $storage, int $hardFailLimit, int $intervalSeconds) {
        $this->storage = $storage;
        $this->hardFailLimit = $hardFailLimit;
        $this->intervalSeconds = $intervalSeconds;
    }

    /**
     * @param DateTimeInterface|null $time
     *
     * @throws LockWaitTimeoutException
     * @throws HardFailLimitReachedException
     * @throws Exception
     */
    public function recordFailure(DateTimeInterface $time = null): void {
        if (is_null($time)) {
            $time = new DateTimeImmutable('now');
        }

        $this->storageData = new StorageData();
        $this->storage->lock();
        $this->storage->load($this->storageData);

        $this->storageData->addFailPoint($time);
        $this->storageData->expire($this->intervalSeconds);

        $this->storage->save($this->storageData);
        $this->storage->unlock();

        $failCnt = $this->storageData->getFailCount();
        if ($failCnt >= $this->hardFailLimit) {
            throw new HardFailLimitReachedException($this->hardFailLimit);
        }
    }

    /**
     * @throws Exception
     */
    public function clearFailPoints(): void {
        $this->storageData = new StorageData();
        $this->storage->lock();
        $this->storage->load($this->storageData);

        $this->storageData->clear();

        $this->storage->save($this->storageData);
        $this->storage->unlock();
    }
}