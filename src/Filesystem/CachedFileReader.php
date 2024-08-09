<?php

declare(strict_types=1);

namespace MemoryLimiter\Filesystem;

/**
 * @internal
 */
final class CachedFileReader implements FileReader
{
    private readonly FileReader $fileReader;

    /**
     * @var array<string, string|null> File content keyed by file path
     */
    private array $cache = [];

    public function __construct()
    {
        $this->fileReader = new NativeFileReader();
    }

    public function getFileContent(string $filePath): ?string
    {
        if (!isset($this->cache[$filePath])) {
            $this->cache[$filePath] = $this->fileReader->getFileContent($filePath);
        }

        return $this->cache[$filePath];
    }

    public function clearCache(): void
    {
        $this->cache = [];
    }
}
