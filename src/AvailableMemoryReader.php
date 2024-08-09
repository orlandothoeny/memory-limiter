<?php

declare(strict_types=1);

namespace MemoryLimiter;

use MemoryLimiter\Exception\CgroupMemoryLimitIsMaxException;
use MemoryLimiter\Exception\FailedToDetermineAvailableMemoryException;
use MemoryLimiter\Filesystem\CachedFileReader;
use MemoryLimiter\Filesystem\FileReader;
use MemoryLimiter\Filesystem\NativeFileReader;

final readonly class AvailableMemoryReader
{
    public static function create(): self
    {
        return new self(
            new NativeFileReader(),
            new CachedFileReader(),
        );
    }

    public function __construct(
        private FileReader $fileReader,
        private CachedFileReader $cachedFileReader,
        private string $procMeminfoFilePath = '/proc/meminfo',
        private string $cgroupMemoryCurrentFilePath = '/sys/fs/cgroup/memory.current',
        private string $cgroupMemoryMaxFilePath = '/sys/fs/cgroup/memory.max',
    ) {}

    /**
     * @throws FailedToDetermineAvailableMemoryException
     *
     * @return int The amount of memory that is currently free and can be consumed by currently running or new processes
     */
    public function determineAvailableMemoryBytes(): int
    {
        /* We prefer to use Gcroup memory information if available,
        because the /proc/meminfo files always displays the memory-information for the whole system.
        Which does not apply when running inside a container that may not use the whole system memory.
        For example when running on a Kubernetes Node or as one of many containers on a VM */
        $cgroupAvailableMemoryBytes = $this->getCgroupAvailableMemoryBytes();

        if ($cgroupAvailableMemoryBytes !== null) {
            return $cgroupAvailableMemoryBytes;
        }

        $systemAvailableMemoryBytes = $this->getAvailableSystemMemoryBytes();

        if ($systemAvailableMemoryBytes !== null) {
            $this->cachedFileReader->clearCache();

            return $systemAvailableMemoryBytes;
        }

        $systemMemoryLimitBytes = $this->getTotalSystemMemoryBytes();

        if ($systemMemoryLimitBytes !== null) {
            $this->cachedFileReader->clearCache();

            return $systemMemoryLimitBytes;
        }

        throw new FailedToDetermineAvailableMemoryException();
    }

    /**
     * @throws CgroupMemoryLimitIsMaxException
     *
     * @return int|null Total amount of cgroup memory bytes
     *                  See https://www.kernel.org/doc/html/latest/admin-guide/cgroup-v2.html#memory-interface-files
     */
    private function getMaxCgroupMemoryBytes(): ?int
    {
        $maxMemoryFileContent = $this->fileReader->getFileContent($this->cgroupMemoryMaxFilePath);

        if ($maxMemoryFileContent === null) {
            return null;
        }

        // "max" is the default value if no limit is set (limit = all the system memory)
        if ($maxMemoryFileContent === 'max') {
            throw new CgroupMemoryLimitIsMaxException();
        }

        return (int) $maxMemoryFileContent;
    }

    /**
     * @return int|null Amount of cgroup memory bytes that are currently in use
     *                  See See https://www.kernel.org/doc/html/latest/admin-guide/cgroup-v2.html#memory-interface-files
     */
    private function getCurrentCgroupMemoryUsageBytes(): ?int
    {
        $currentMemoryFileContent = $this->fileReader->getFileContent($this->cgroupMemoryCurrentFilePath);

        if ($currentMemoryFileContent === null) {
            return null;
        }

        return (int) $currentMemoryFileContent;
    }

    /**
     * @return int|null Amount of cgroup memory bytes that are currently unused
     */
    private function getCgroupAvailableMemoryBytes(): ?int
    {
        try {
            $memoryLimit = $this->getMaxCgroupMemoryBytes();
        } catch (CgroupMemoryLimitIsMaxException) {
            /* If no limit is set, and we would subtract the cgroup memory usage from the system memory limit,
            then the result would with a high likelihood be higher than the actually available memory bytes.
            Since there are likely other processes running on the system.
            Thus, we abort and treat an unconfigured cgroup memory limit as if it was not available */
            return null;
        }

        $memoryUsage = $this->getCurrentCgroupMemoryUsageBytes();

        if ($memoryLimit === null || $memoryUsage === null) {
            return null;
        }

        $availableMemory = $memoryLimit - $memoryUsage;

        if ($availableMemory <= 0) {
            return null;
        }

        return $availableMemory;
    }

    private function getProcMeminfoValueInBytes(string $itemKey): ?int
    {
        $meminfoFileContent = $this->cachedFileReader->getFileContent($this->procMeminfoFilePath);

        if ($meminfoFileContent === null) {
            return null;
        }

        $meminfoLines = \explode("\n", $meminfoFileContent);

        foreach ($meminfoLines as $meminfoLine) {
            $meminfoLineParts = \explode(':', $meminfoLine);

            if (\count($meminfoLineParts) !== 2) {
                continue;
            }

            $key   = \trim($meminfoLineParts[0]);
            $value = \trim($meminfoLineParts[1]);

            if ($key === $itemKey) {
                return $this->extractMeminfoItemInBytes($value);
            }
        }

        return null;
    }

    /**
     * @param string $meminfoLine A single line from /proc/meminfo
     *
     * @return int|null The value of the line converted to bytes
     */
    private function extractMeminfoItemInBytes(string $meminfoLine): ?int
    {
        $itemParts = \explode(' ', $meminfoLine);

        if (\count($itemParts) !== 2) {
            return null;
        }

        $itemValue = $itemParts[0];

        if (!\is_numeric($itemValue)) {
            return null;
        }

        $itemValue = (int) $itemValue;
        $itemUnit  = $itemParts[1];

        if ($itemUnit === 'kB') {
            return $itemValue * 1024;
        }

        return null;
    }

    /**
     * @return int|null An estimate of how much memory is available for starting new
     *                  applications, without swapping. Calculated from MemFree,
     *                  SReclaimable, the size of the file LRU lists, and the low
     *                  watermarks in each zone.
     *                  The estimate takes into account that the system needs some
     *                  page cache to function well, and that not all reclaimable
     *                  slab will be reclaimable, due to items being in use. The
     *                  impact of those factors will vary from system to system.
     *                  See https://docs.kernel.org/filesystems/proc.html#meminfo
     */
    private function getAvailableSystemMemoryBytes(): ?int
    {
        return $this->getProcMeminfoValueInBytes('MemAvailable');
    }

    /**
     * @return int|null Total usable ram (i.e. physical ram minus a few reserved bits and the kernel binary code)
     *                  See https://docs.kernel.org/filesystems/proc.html#meminfo
     */
    private function getTotalSystemMemoryBytes(): ?int
    {
        return $this->getProcMeminfoValueInBytes('MemTotal');
    }
}
