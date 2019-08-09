<?php

namespace SoftFailer\Exception;

use Exception;
use Throwable;

class LockWaitTimeoutException extends Exception {
    public function __construct(string $filename, int $ms, Throwable $previous = null) {
        $message = 'lock timeout after ' . $ms . ' ms for ' . $filename;
        parent::__construct($message, 0, $previous);
    }
}