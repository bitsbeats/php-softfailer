<?php

namespace SoftFailer\Storage;

use SoftFailer\StorageData;

class Memory implements Storage {
    /**
     * File constructor.
     */
    public function __construct() {
    }

    /**
     * @param StorageData $storageData
     *
     * @return void
     */
    public function load(StorageData $storageData): void {
    }

    /**
     * @param StorageData $storageData
     */
    public function save(StorageData $storageData): void {
    }
}