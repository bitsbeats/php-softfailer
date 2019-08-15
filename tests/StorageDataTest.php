<?php

use SoftFailer\SoftFailer;

require __DIR__ . '/../vendor/autoload.php';

class StorageDataTest extends \PHPUnit\Framework\TestCase {
    public function testAddFailPoint() {
        $time1 = '2019-01-01T10:00:00+00:00';
        $time2 = '2019-01-01T20:00:00+00:00';

        $storageData = new \SoftFailer\StorageData();
        $storageData->addFailPoint(new DateTimeImmutable($time1));
        $storageData->addFailPoint(new DateTimeImmutable($time2));

        $failPoints = $storageData->getFailPoints();
        $this->assertCount(2, $failPoints);

        $failPoint1 = array_shift($failPoints);
        $this->assertEquals($time1, $failPoint1->format(DateTime::RFC3339));

        $failPoint2 = array_shift($failPoints);
        $this->assertEquals($time2, $failPoint2->format(DateTime::RFC3339));
    }

    public function testAddDuplicateFailPoint() {
        $time = '2019-01-01T10:00:00+00:00';

        $storageData = new \SoftFailer\StorageData();
        $storageData->addFailPoint(new DateTimeImmutable($time));
        $storageData->addFailPoint(new DateTimeImmutable($time));

        $this->assertCount(2, $storageData->getFailPoints());

        $ident = 'test';
        $storageData->addFailPoint(new DateTimeImmutable($time), $ident);
        $storageData->addFailPoint(new DateTimeImmutable($time), $ident);
        $this->assertCount(3, $storageData->getFailPoints());
    }

    public function testExpire() {
        $time1 = '2019-01-01T10:00:00+00:00';
        $time2 = '2019-01-01T20:00:00+00:00';

        $storageData = new \SoftFailer\StorageData();
        $storageData->addFailPoint(new DateTimeImmutable($time1));
        $storageData->addFailPoint(new DateTimeImmutable($time2));

        $dtExpire = (new DateTimeImmutable($time2))->modify('+3600 seconds');

        $storageData->expire(86400, $dtExpire);
        $this->assertCount(2, $storageData->getFailPoints());

        $storageData->expire(7200, $dtExpire);
        $this->assertCount(1, $storageData->getFailPoints());

        $storageData->expire(500, $dtExpire);
        $this->assertCount(0, $storageData->getFailPoints());
    }
}