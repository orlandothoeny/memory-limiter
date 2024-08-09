<?php

declare(strict_types=1);

namespace MemoryLimiter;

use MemoryLimiter\Exception\FailedToDetermineAvailableMemoryException;
use MemoryLimiter\Exception\InvalidPercentageLimitOfAvailableMemoryException;
use Psr\Log\LoggerInterface;

final readonly class MemoryLimiter
{
    public static function create(?LoggerInterface $logger = null): self
    {
        return new self(
            AvailableMemoryReader::create(),
            new RuntimeEnvironmentDetector(),
            $logger,
        );
    }

    public function __construct(
        private AvailableMemoryReader $availableMemoryReader,
        private RuntimeEnvironmentDetector $runtimeEnvironmentDetector,
        private ?LoggerInterface $logger = null,
    ) {}

    /**
     * Sets the PHP memory limit to the amount of memory that is currently not in use and free to be consumed by a process
     *
     * @param bool       $skipIfRunningInsideKubernetesContainer If true, setting the memory limit will be skipped if the application is running inside a Kubernetes container.
     *                                                           Usually, all the memory of a Kubernetes container may be used by its processes, since it is not shared with other processes or containers.
     *                                                           This might not be the case when multiple processes are running in the same container
     * @param float|null $limitToPercentageOfAvailableMemory     If set, the memory limit will be set to the given percentage of the amount of memory that is available
     *                                                           Must be greater than 0 and less than 100
     *
     * @throws FailedToDetermineAvailableMemoryException
     * @throws InvalidPercentageLimitOfAvailableMemoryException
     */
    public function setMemoryLimitToCurrentlyAvailableMemory(
        bool $skipIfRunningInsideKubernetesContainer = true,
        ?float $limitToPercentageOfAvailableMemory = null,
    ): void {
        if (
            $skipIfRunningInsideKubernetesContainer
            && $this->runtimeEnvironmentDetector->isRunningInsideKubernetesContainer()
        ) {
            $this->logger?->notice('Skipping setting memory limit because running inside Kubernetes container');

            return;
        }

        $memoryLimit = $this->availableMemoryReader->determineAvailableMemoryBytes();

        if ($limitToPercentageOfAvailableMemory !== null) {
            if ($limitToPercentageOfAvailableMemory <= 0 || $limitToPercentageOfAvailableMemory >= 100) {
                throw new InvalidPercentageLimitOfAvailableMemoryException($limitToPercentageOfAvailableMemory);
            }

            $originalMemoryLimit = $memoryLimit;

            $memoryLimit = (int) ($memoryLimit * ($limitToPercentageOfAvailableMemory / 100));

            $this->logger?->info('Reducing memory limit from {original_memory_limit} to {memory_limit} bytes ({percentage}% of original)', [
                'original_memory_limit' => $originalMemoryLimit,
                'memory_limit'          => $memoryLimit,
                'percentage'            => $limitToPercentageOfAvailableMemory,
            ]);
        }

        $this->logger?->notice('Setting memory limit to {memory_limit} bytes', ['memory_limit' => $memoryLimit]);

        \ini_set('memory_limit', (string) $memoryLimit);
    }
}
