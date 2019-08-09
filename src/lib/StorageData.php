<?php

namespace SoftFailer;

use DateTimeImmutable;
use Exception;
use DateTime;
use DateTimeInterface;
use Ramsey\Uuid\Uuid;

class StorageData {
    /** @var array */
    private $failPoints = [];

    /**
     */
    public function __construct() {
    }

    /**
     * @param DateTimeInterface $time
     * @param string            $ident
     * @throws Exception
     */
    public function addFailPoint(DateTimeInterface $time, string $ident = ''): void {
        if (!$ident) {
            $ident = Uuid::uuid4()->toString();
        }

        // ignore existing idents
        if (array_key_exists($ident, $this->failPoints)) {
            return;
        }

        $this->failPoints[$ident] = $time;
    }

    /**
     * @param string $timeFormat
     * @return array
     */
    public function toStrings(string $timeFormat = DateTime::RFC3339_EXTENDED): array {
        $strings = [];
        foreach($this->failPoints as $ident => $failPoint) {
            $strings[$ident] = $failPoint->format($timeFormat);
        }
        return $strings;
    }

    /**
     * @param array $strings
     *
     * @throws Exception
     */
    public function addFromStrings(array $strings): void {
        foreach($strings as $ident => $string) {
            $dt = new DateTimeImmutable($string);
            $this->addFailPoint($dt, $ident);
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
        foreach($this->failPoints as $ident => $time) {
            if ($time < $expireTime) {
                unset($this->failPoints[$ident]);
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