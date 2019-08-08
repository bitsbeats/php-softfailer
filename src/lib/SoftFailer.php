<?php

namespace SoftFailer;

use Exception;
use DateTimeImmutable;
use DateTimeInterface;
use SoftFailer\Exception\HardFailLimitReachedException;
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

        $this->storageData = new StorageData();
        $storage->load($this->storageData);
        $this->storageData->expire($intervalSeconds);
    }

    /**
     * @param DateTimeInterface|null $time
     *
     * @throws HardFailLimitReachedException
     * @throws Exception
     */
    public function recordFailure(DateTimeInterface $time = null): void {
        if (is_null($time)) {
            $time = new DateTimeImmutable('now');
        }
        $this->storageData->addFailPoint($time);
        $this->storageData->expire($this->intervalSeconds);

        $this->storage->save($this->storageData);

        $failCnt = $this->storageData->getFailCount();
        if ($failCnt >= $this->hardFailLimit) {
            throw new HardFailLimitReachedException('hard fail limit reached');
        }
    }

    /**
     *
     */
    public function clearFailPoints(): void {
        $this->storageData->clear();
        $this->storage->save($this->storageData);
    }
}