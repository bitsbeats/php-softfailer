<?php

require_once(__DIR__ . "/vendor/autoload.php");

use SoftFailer\SoftFailer;
use SoftFailer\Storage\Filesystem;
use SoftFailer\Exception\HardFailLimitReachedException;
use SoftFailer\Exception\LockWaitTimeoutException;


$storage = new Filesystem('/tmp/softfail.txt', '/tmp/softfail.lock', 500);

try {
    // hard fail if 3 or more "soft fails" occur within a 3600 second time window
    $sf = new SoftFailer($storage, 3, 3600);
    $sf->recordFailure(new DateTime());
}
catch (HardFailLimitReachedException $e) {
    // a "hard fail" is triggered by throwing a "HardFailLimitReachedException" exception
    print "FAIL: {$e->getMessage()}\n";
    try {
        $sf->clearFailPoints();
    }
    catch (Exception $e) {}
    exit(1);
}
catch (LockWaitTimeoutException $e) {
    // ignore timeouts, it just means a failure has not been recorded
}
catch (Exception $e) {
    print "ERROR: {$e->getMessage()}\n";
    exit(1);
}
