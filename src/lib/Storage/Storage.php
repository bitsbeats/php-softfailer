<?php

namespace SoftFailer\Storage;

use SoftFailer\StorageData;

interface Storage {
    function load(StorageData $data): void;
    function save(StorageData $data): void;
    function lock(): void;
    function unlock(): void;
}