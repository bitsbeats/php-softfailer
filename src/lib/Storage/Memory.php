<?php

namespace SoftFailer\Storage;

use Exception;
use SoftFailer\StorageData;

/**
 * Dummy storage driver (for testing only)
 */
class Memory implements Storage {
    /**
     * @var array
     */
    private $failPoints = [];

    /**
     * File constructor.
     */
    public function __construct() {
    }

    /**
     * @param StorageData $storageData
     *
     * @throws Exception
     */
    public function load(StorageData $storageData): void {
        foreach($this->failPoints as $ident => $failPoint) {
            $storageData->addFailPoint($failPoint, $ident);
        }
    }

    /**
     * @param StorageData $storageData
     */
    public function save(StorageData $storageData): void {
        $failPoints = $storageData->getFailPoints();
        foreach($failPoints as $ident => $failPoint) {
            $this->failPoints[$ident] = $failPoint;
        }
    }

    /**
     *
     */
    public function lock(): void {
        // no locking necessary
    }

    /**
     *
     */
    public function unlock(): void {
        // no locking necessary
    }
}