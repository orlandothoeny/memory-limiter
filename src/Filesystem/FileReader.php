<?php

declare(strict_types=1);

namespace MemoryLimiter\Filesystem;

/**
 * @internal
 */
interface FileReader
{
    /**
     * @return string|null Trimmed file content or null if file does not exist
     */
    public function getFileContent(string $filePath): ?string;
}
