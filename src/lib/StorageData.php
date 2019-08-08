<?php

namespace SoftFailer;

use DateTimeImmutable;
use Exception;
use DateTime;
use DateTimeInterface;

class StorageData {
    /** @var array */
    private $failPoints = [];

    /**
     */
    public function __construct() {
    }

    /**
     * @param DateTimeInterface $time
     */
    public function addFailPoint(DateTimeInterface $time): void {
        $this->failPoints[] = $time;
    }

    /**
     * @param string $timeFormat
     * @return array
     */
    public function toStrings(string $timeFormat = DateTime::RFC3339_EXTENDED): array {
        $strings = [];
        foreach($this->failPoints as $failPoint) {
            $strings[] = $failPoint->format($timeFormat);
        }
        return $strings;
    }

    /**
     * @param array $strings
     *
     * @throws Exception
     */
    public function addFromStrings(array $strings): void {
        // TODO: check for duplicates
        foreach($strings as $string) {
            $dt = new DateTimeImmutable($string);
            $this->addFailPoint($dt);
        }
    }

    /**
     * @return int
     * @throws Exception
     */
    public function getFailCount(): int {
        return count($this->failPoints);
    }

    /**
     * @param int $expireSeconds
     * @throws Exception
     */
    public function expire(int $expireSeconds): void {
        $expireTime = (new DateTime('now'))->modify('-' . $expireSeconds . 'seconds');
        foreach($this->failPoints as $idx => $time) {
            if ($time < $expireTime) {
                unset($this->failPoints[$idx]);
            }
        }
    }

    /**
     *
     */
    public function clear(): void {
        $this->failPoints = [];
    }
}