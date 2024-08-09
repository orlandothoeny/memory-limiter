<?php

declare(strict_types=1);

namespace MemoryLimiter\Exception;

final class InvalidPercentageLimitOfAvailableMemoryException extends \Exception
{
    public function __construct(float $percentage)
    {
        parent::__construct(\sprintf('%s is not a valid percentage to apply to the available memory, it must be between 0 and 100', $percentage));
    }
}
