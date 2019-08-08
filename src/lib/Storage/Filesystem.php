<?php

namespace SoftFailer\Storage;

use Exception;
use SoftFailer\StorageData;

class Filesystem implements Storage {
    /** @var string */
    private $filename;

    /**
     * File constructor.
     *
     * @param string $filename
     */
    public function __construct(string $filename) {
        $this->filename = $filename;
    }

    /**
     * @param StorageData $storageData
     * @throws Exception
     */
    public function load(StorageData $storageData): void {
        if (!file_exists($this->filename)) {
            // if file does not yet exist, just don't load
            return;
        }

        $dataRaw = file_get_contents($this->filename);
        if ($dataRaw === false) {
            throw new Exception("can't load data from file {$this->filename}");
        }

        $data = json_decode($dataRaw, true);
        if ($data === false) {
            throw new Exception("can't decode JSON: " . $dataRaw);
        }

        if (!isset($data['failPoints']) || !is_array($data['failPoints'])) {
            throw new Exception("no failsPoints found: " . $dataRaw);
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
        $result = file_put_contents($this->filename, $dataRaw);

        if (!$result) {
            throw new Exception("can't save data to file {$this->filename}");
        }
    }
}