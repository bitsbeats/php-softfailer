# bitsbeats/php-softfailer

Library to turn soft failures occurring within a certain time interval into
hard failures, with persisting state.

## Features
- uses persistent storage, so that failures are counted among multiple script calls
or pageviews
- storage drivers for filesystem, memcache and APCu

## Install
The easiest way to install SoftFailer is by using [composer](https://getcomposer.org/): 

```
$> composer require bitsbeats/softfailer
```

## Usage

```php
$storage = new Filesystem('/tmp/softfail.txt');

// hard fail if 3 or more "soft fails" occur within a 3600 second time window
$sf = new SoftFailer($storage, 3, 3600);

try {
    $sf->recordFailure(new DateTime());
}
catch (HardFailLimitReachedException $e) {
    // a "hard fail" is triggered by throwing a "HardFailLimitReachedException" exception
    print "FAIL: {$e->getMessage()}\n";
    $sf->clearFailPoints();
    exit(1);
}
```

See `example.php` for the full example.
