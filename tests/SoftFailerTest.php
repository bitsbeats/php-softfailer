<?php

use SoftFailer\SoftFailer;

require __DIR__ . '/../vendor/autoload.php';

class SoftFailerTest extends \PHPUnit\Framework\TestCase {
    public function testHardFail() {
        $dtNow = new DateTimeImmutable('now');

        $storage = new \SoftFailer\Storage\Memory();
        $softfailer = new SoftFailer($storage, 3, 1);

        $softfailer->recordFailure($dtNow);
        $softfailer->recordFailure($dtNow);

        $this->expectException(\SoftFailer\Exception\HardFailLimitReachedException::class);
        $softfailer->recordFailure($dtNow);
    }

    public function testNoHardFailOutsideInterval() {
        $dtNow = new DateTimeImmutable('now');
        $dtTenSecondsBefore = $dtNow->modify('-20 seconds');

        $this->expectNotToPerformAssertions();

        $storage = new \SoftFailer\Storage\Memory();
        $softfailer = new SoftFailer($storage, 3, 10);

        $softfailer->recordFailure($dtTenSecondsBefore);
        $softfailer->recordFailure($dtNow);
        $softfailer->recordFailure($dtNow);
    }
}