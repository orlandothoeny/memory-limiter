<?php

/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types=1);

namespace MemoryLimiter\Tests;

use MemoryLimiter\AvailableMemoryReader;
use MemoryLimiter\Exception\CgroupMemoryLimitIsMaxException;
use MemoryLimiter\Exception\FailedToDetermineAvailableMemoryException;
use MemoryLimiter\Filesystem\CachedFileReader;
use MemoryLimiter\Filesystem\NativeFileReader;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AvailableMemoryReader::class)]
#[CoversClass(CgroupMemoryLimitIsMaxException::class)]
class AvailableMemoryReaderTest extends TestCase
{
    public function testDetermineAvailableMemoryBytes_bareMetalUbuntu_shouldUseProcMeminfoMemAvailable(): void
    {
        $availableMemoryReader = $this->buildAvailableMemoryReader(
            __DIR__ . '/data/BareMetalUbuntu',
        );

        self::assertSame(
            15985380 * 1024, // 15985380 KiB in bytes
            $availableMemoryReader->determineAvailableMemoryBytes(),
        );
    }

    public function testDetermineAvailableMemoryBytes_bareMetalOldLinuxKernelWithDifferentProfMeminfoFormat_shouldUseProcMeminfoMemTotal(): void
    {
        $availableMemoryReader = $this->buildAvailableMemoryReader(
            __DIR__ . '/data/BareMetalLinuxKernel2.6.19',
        );

        self::assertSame(
            5997568 * 1024, // 5997568 KiB in bytes
            $availableMemoryReader->determineAvailableMemoryBytes(),
        );
    }

    public function testDetermineAvailableMemoryBytes_bareMetalMissingMemAvailable_shouldUseProcMeminfoMemTotal(): void
    {
        $availableMemoryReader = $this->buildAvailableMemoryReader(
            __DIR__ . '/data/BareMetalMissingMemAvailable',
        );

        self::assertSame(
            32588068 * 1024, // 32588068 KiB in bytes
            $availableMemoryReader->determineAvailableMemoryBytes(),
        );
    }

    public function testDetermineAvailableMemoryBytes_ubuntuDockerContainerWithoutResourceLimits_shouldUseProcMeminfoMemAvailable(): void
    {
        $availableMemoryReader = $this->buildAvailableMemoryReader(
            __DIR__ . '/data/UbuntuDockerContainerWithoutResourceLimits',
        );

        self::assertSame(
            16020264 * 1024, // 16020264 KiB in bytes
            $availableMemoryReader->determineAvailableMemoryBytes(),
        );
    }

    public function testDetermineAvailableMemoryBytes_ubuntuKubernetesContainer_shouldCalculateCgroupAvailableMemory(): void
    {
        $availableMemoryReader = $this->buildAvailableMemoryReader(
            __DIR__ . '/data/UbuntuKubernetesContainer',
        );

        self::assertSame(
            1342177280 - 215252992, // memory.max - memory.current
            $availableMemoryReader->determineAvailableMemoryBytes(),
        );
    }

    public function testDetermineAvailableMemoryBytes_windows_shouldThrowException(): void
    {
        $availableMemoryReader = $this->buildAvailableMemoryReader(
            __DIR__ . '/data/Windows',
        );

        $this->expectException(FailedToDetermineAvailableMemoryException::class);

        $availableMemoryReader->determineAvailableMemoryBytes();
    }

    private function buildAvailableMemoryReader(string $testDataDirectory): AvailableMemoryReader
    {
        return new AvailableMemoryReader(
            new NativeFileReader(),
            new CachedFileReader(),
            $testDataDirectory . '/proc/meminfo',
            $testDataDirectory . '/sys/fs/cgroup/memory.current',
            $testDataDirectory . '/sys/fs/cgroup/memory.max',
        );
    }
}
