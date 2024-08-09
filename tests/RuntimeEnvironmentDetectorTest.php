<?php

/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types=1);

namespace MemoryLimiter\Tests;

use MemoryLimiter\RuntimeEnvironmentDetector;
use PHPUnit\Framework\Attributes\BackupGlobals;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(RuntimeEnvironmentDetector::class)]
#[BackupGlobals(true)]
class RuntimeEnvironmentDetectorTest extends TestCase
{
    public function tearDown(): void
    {
        $this->resetEnvironment();
    }

    public function testIsRunningInsideKubernetesContainer(): void
    {
        \putenv('KUBERNETES_SERVICE_HOST=1.2.3.4');
        \putenv('KUBERNETES_SERVICE_PORT=1234');
        \putenv('KUBERNETES_PORT_1_2_3_4=1234');

        $runtimeEnvironmentDetector = new RuntimeEnvironmentDetector(
            __DIR__ . '/data/UbuntuKubernetesContainer/var/run/secrets/kubernetes.io',
        );

        self::assertTrue($runtimeEnvironmentDetector->isRunningInsideKubernetesContainer());
    }

    public function testIsRunningInsideKubernetesContainer_whenSecretsDirectoryDoesNotExist_thenFalseShouldBeReturned(): void
    {
        \putenv('KUBERNETES_SERVICE_HOST=1.2.3.4');
        \putenv('KUBERNETES_SERVICE_PORT=1234');
        \putenv('KUBERNETES_PORT_1_2_3_4=1234');

        $runtimeEnvironmentDetector = new RuntimeEnvironmentDetector(
            __DIR__ . '/data/UbuntuDockerContainerWithoutResourceLimits/var/run/secrets/kubernetes.io',
        );

        self::assertFalse($runtimeEnvironmentDetector->isRunningInsideKubernetesContainer());
    }

    public function testIsRunningInsideKubernetesContainer_whenNoKubernetesEnvironmentVariablesAreSet_thenFalseShouldBeReturned(): void
    {
        $runtimeEnvironmentDetector = new RuntimeEnvironmentDetector(
            __DIR__ . '/data/UbuntuKubernetesContainer/var/run/secrets/kubernetes.io',
        );

        self::assertFalse($runtimeEnvironmentDetector->isRunningInsideKubernetesContainer());
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
