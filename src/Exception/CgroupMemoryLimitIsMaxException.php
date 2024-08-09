<?php

declare(strict_types=1);

namespace MemoryLimiter\Exception;

/**
 * @internal
 */
final class CgroupMemoryLimitIsMaxException extends \Exception
{
    public function __construct()
    {
        parent::__construct('Cgroup memory limit is set to the default value "max"');
    }
}
