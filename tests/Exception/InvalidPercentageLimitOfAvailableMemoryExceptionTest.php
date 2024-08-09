<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types=1);

namespace MemoryLimiter\Tests\Exception;

use MemoryLimiter\Exception\InvalidPercentageLimitOfAvailableMemoryException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(InvalidPercentageLimitOfAvailableMemoryException::class)]
class InvalidPercentageLimitOfAvailableMemoryExceptionTest extends TestCase
{
    public function testConstruct(): void
    {
        $exception = new InvalidPercentageLimitOfAvailableMemoryException(50);

        self::assertSame('50 is not a valid percentage to apply to the available memory, it must be between 0 and 100', $exception->getMessage());
    }
}
