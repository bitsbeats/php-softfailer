<?php

namespace SoftFailer\Storage;

use Exception;
use SoftFailer\Exception\LockWaitTimeoutException;
use SoftFailer\StorageData;

interface Storage {
    /**
     * @param StorageData $data
     * @throws Exception
     */
    function load(StorageData $data): void;

    /**
     * @param StorageData $data
     * @throws Exception
     */
    function save(StorageData $data): void;

    /**
     * @throws Exception
     * @throws LockWaitTimeoutException
     */
    function lock(): void;

    /**
     * @throws Exception
     */
    function unlock(): void;
}