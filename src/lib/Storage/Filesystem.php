<?php

namespace SoftFailer\Storage;

use Exception;
use SoftFailer\Exception\LockWaitTimeoutException;
use SoftFailer\StorageData;

class Filesystem implements Storage {
    /** @var int */
    private $sleepTimeMS = 10;

    /** @var string */
    private $filename;

    /** @var string */
    private $lockFilename;

    /** @var int */
    private $timeoutMS;

    /** @var resource */
    private $lockFilehandle = null;

    /**
     * File constructor.
     *
     * @param string $filename
     * @param string $lockFilename
     * @param int    $timeoutMS
     */
    public function __construct(string $filename, string $lockFilename, int $timeoutMS) {
        $this->filename = $filename;
        $this->lockFilename = $lockFilename;
        $this->timeoutMS = $timeoutMS;
    }

    /**
     * @param int $sleepTimeMS
     *
     * @return Filesystem
     */
    public function setSleepTimeMS(int $sleepTimeMS): Filesystem {
        $this->sleepTimeMS = $sleepTimeMS;
        return $this;
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
        $result = file_put_contents($this->filename, $dataRaw);

        if (!$result) {
            throw new Exception("can't save data to file {$this->filename}");
        }
    }

    /**
     * @throws LockWaitTimeoutException
     * @throws Exception
     */
    public function lock(): void {
        $fh = fopen($this->lockFilename, 'c+');
        if ($fh === false) {
            throw new Exception('cannot open lock file: ' . $this->lockFilename);
        }

        $timeStart = microtime(true);
        do {
            if (flock($fh, LOCK_EX | LOCK_NB)) {
                $this->lockFilehandle = $fh;
                return;
            }
            usleep(1000 * $this->sleepTimeMS);
            $msPassed = intval((microtime(true) - $timeStart) * 1000);
        }
        while($msPassed < $this->timeoutMS);
        throw new LockWaitTimeoutException($this->lockFilename, $msPassed);
    }

    /**
     *
     */
    public function unlock(): void {
        if (is_null($this->lockFilehandle)) {
            return;
        }

        flock($this->lockFilehandle, LOCK_UN);
        fclose($this->lockFilehandle);
        $this->lockFilehandle = null;
    }
}