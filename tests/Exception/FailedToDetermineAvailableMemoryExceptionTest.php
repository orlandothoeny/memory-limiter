<?php

/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types=1);

namespace MemoryLimiter\Tests\Exception;

use MemoryLimiter\Exception\FailedToDetermineAvailableMemoryException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(FailedToDetermineAvailableMemoryException::class)]
class FailedToDetermineAvailableMemoryExceptionTest extends TestCase
{
    public function testConstruct(): void
    {
        $exception = new FailedToDetermineAvailableMemoryException();

        self::assertSame('Failed to determine available memory', $exception->getMessage());
    }
}
