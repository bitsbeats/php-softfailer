<?php

namespace SoftFailer\Exception;

use Exception;
use Throwable;

class HardFailLimitReachedException extends Exception {
    public function __construct(int $limit, Throwable $previous = null) {
        $message = 'hard fail limit of ' . $limit . ' reached';
        parent::__construct($message, 0, $previous);
    }
}