<?php

require_once(__DIR__ . "/vendor/autoload.php");

use SoftFailer\SoftFailer;
use SoftFailer\Storage\Filesystem;
use SoftFailer\Exception\HardFailLimitReachedException;

$storage = new Filesystem(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'softfail.txt');

try {
    // hard fail if 3 or more "soft fails" occur within a 3600 second time window
    $sf = new SoftFailer($storage, 3, 3600);
    $sf->recordFailure(new DateTime());
}
catch (HardFailLimitReachedException $e) {
    // a "hard fail" is triggered by throwing a "HardFailLimitReachedException" exception
    print "FAIL: {$e->getMessage()}\n";
    $sf->clearFailPoints();
    exit(1);
}
catch (Exception $e) {
    print "ERROR: {$e->getMessage()}\n";
    exit(1);
}
