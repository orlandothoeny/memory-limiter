<?php

declare(strict_types=1);

namespace MemoryLimiter;

/**
 * @internal
 */
final readonly class RuntimeEnvironmentDetector
{
    public function __construct(
        private string $kubernetesSecretsDirectory = '/var/run/secrets/kubernetes.io',
    ) {}

    public function isRunningInsideKubernetesContainer(): bool
    {
        return $this->hasKubernetesEnvironmentVariables() && \file_exists($this->kubernetesSecretsDirectory);
    }

    private function hasKubernetesEnvironmentVariables(): bool
    {
        $environmentVariables = \getenv();

        if (!\is_array($environmentVariables)) {
            return false;
        }

        foreach ($environmentVariables as $key => $value) {
            if (\str_starts_with($key, 'KUBERNETES_')) {
                return true;
            }
        }

        return false;
    }
}
