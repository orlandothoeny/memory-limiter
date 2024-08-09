<?php

declare(strict_types=1);

namespace MemoryLimiter\Exception;

final class FailedToDetermineAvailableMemoryException extends \Exception
{
    public function __construct()
    {
        parent::__construct('Failed to determine available memory');
    }
}
