<?php

/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types=1);

namespace MemoryLimiter\Tests;

use MemoryLimiter\AvailableMemoryReader;
use MemoryLimiter\Exception\InvalidPercentageLimitOfAvailableMemoryException;
use MemoryLimiter\Filesystem\CachedFileReader;
use MemoryLimiter\Filesystem\NativeFileReader;
use MemoryLimiter\MemoryLimiter;
use MemoryLimiter\RuntimeEnvironmentDetector;
use PHPUnit\Framework\Attributes\BackupGlobals;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(MemoryLimiter::class)]
#[BackupGlobals(true)]
class MemoryLimiterTest extends TestCase
{
    private static string $initialMemoryLimit;

    public static function setUpBeforeClass(): void
    {
        $memoryLimit = \ini_get('memory_limit');

        if ($memoryLimit === false) {
            throw new \RuntimeException(\sprintf('%s: Failed to determine initial memory limit', __METHOD__));
        }

        self::$initialMemoryLimit = $memoryLimit;
    }

    public function tearDown(): void
    {
        $this->resetEnvironment();

        \ini_set('memory_limit', self::$initialMemoryLimit);
    }

    public function testSetMemoryLimitToCurrentlyAvailableMemory(): void
    {
        $memoryLimiter = $this->buildMemoryLimiter(
            __DIR__ . '/data/BareMetalUbuntu',
        );

        $memoryLimiter->setMemoryLimitToCurrentlyAvailableMemory();

        self::assertSame(
            15985380 * 1024, // 15985380 KiB in bytes
            (int) \ini_get('memory_limit'),
        );
    }

    public function testSetMemoryLimitToCurrentlyAvailableMemory_whenRunningInsideKubernetesContainer_andSkipFlagIsSet_thenNothingShouldHappen(): void
    {
        $this->setKubernetesEnvironmentVariables();

        $memoryLimiter = $this->buildMemoryLimiter(
            __DIR__ . '/data/UbuntuKubernetesContainer',
        );

        $memoryLimiter->setMemoryLimitToCurrentlyAvailableMemory();

        self::assertSame(
            self::$initialMemoryLimit,
            \ini_get('memory_limit'),
        );
    }

    public function testSetMemoryLimitToCurrentlyAvailableMemory_whenRunningInsideKubernetesContainer_andSkipFlagIsNotSet_thenMemoryLimitShouldBeSet(): void
    {
        $this->setKubernetesEnvironmentVariables();

        $memoryLimiter = $this->buildMemoryLimiter(
            __DIR__ . '/data/UbuntuKubernetesContainer',
        );

        $memoryLimiter->setMemoryLimitToCurrentlyAvailableMemory(false);

        self::assertSame(
            1342177280 - 215252992, // Cgroup files: memory.max - memory.current,
            (int) \ini_get('memory_limit'),
        );
    }

    public function testSetMemoryLimitToCurrentlyAvailableMemory_whenLimitPercentageIsSet_thenMemoryLimitShouldBeDecreased(): void
    {
        $memoryLimiter = $this->buildMemoryLimiter(
            __DIR__ . '/data/BareMetalUbuntu',
        );

        $memoryLimiter->setMemoryLimitToCurrentlyAvailableMemory(limitToPercentageOfAvailableMemory: 10.10);

        self::assertSame(
            1653271941, // 10.1% of 15985380 KiB in bytes
            (int) \ini_get('memory_limit'),
        );
    }

    #[DataProvider('dataProvider_testSetMemoryLimitToCurrentlyAvailableMemory_whenLimitPercentageIsSetToInvalidValue_thenExceptionShouldBeThrown')]
    public function testSetMemoryLimitToCurrentlyAvailableMemory_whenLimitPercentageIsSetToInvalidValue_thenExceptionShouldBeThrown(
        float $limit,
    ): void {
        $memoryLimiter = $this->buildMemoryLimiter(
            __DIR__ . '/data/BareMetalUbuntu',
        );

        $this->expectException(InvalidPercentageLimitOfAvailableMemoryException::class);

        $memoryLimiter->setMemoryLimitToCurrentlyAvailableMemory(limitToPercentageOfAvailableMemory: $limit);
    }

    /**
     * @return array<string, array<int>>
     */
    public static function dataProvider_testSetMemoryLimitToCurrentlyAvailableMemory_whenLimitPercentageIsSetToInvalidValue_thenExceptionShouldBeThrown(): array
    {
        return [
            'negative'         => [-1],
            'zero'             => [0],
            'hundred'          => [100],
            'greater than 100' => [101],
        ];
    }

    private function setKubernetesEnvironmentVariables(): void
    {
        \putenv('KUBERNETES_SERVICE_HOST=1.2.3.4');
        \putenv('KUBERNETES_SERVICE_PORT=1234');
        \putenv('KUBERNETES_PORT_1_2_3_4=1234');
    }

    private function buildMemoryLimiter(string $testDataDirectory): MemoryLimiter
    {
        $availableMemoryReader = new AvailableMemoryReader(
            new NativeFileReader(),
            new CachedFileReader(),
            $testDataDirectory . '/proc/meminfo',
            $testDataDirectory . '/sys/fs/cgroup/memory.current',
            $testDataDirectory . '/sys/fs/cgroup/memory.max',
        );

        return new MemoryLimiter(
            $availableMemoryReader,
            new RuntimeEnvironmentDetector($testDataDirectory . '/var/run/secrets/kubernetes.io'),
        );
    }

    private function resetEnvironment(): void
    {
        \putenv('KUBERNETES_SERVICE_HOST');
        \putenv('KUBERNETES_SERVICE_PORT');
        \putenv('KUBERNETES_PORT_1_2_3_4');

        unset(
            $_SERVER['KUBERNETES_SERVICE_HOST'],
            $_SERVER['KUBERNETES_SERVICE_PORT'],
            $_SERVER['KUBERNETES_PORT_1_2_3_4'],
            $_ENV['KUBERNETES_SERVICE_HOST'],
            $_ENV['KUBERNETES_SERVICE_PORT'],
            $_ENV['KUBERNETES_PORT_1_2_3_4'],
        );
    }
}
