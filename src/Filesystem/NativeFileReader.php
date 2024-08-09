<?php

declare(strict_types=1);

namespace MemoryLimiter\Filesystem;

/**
 * @internal
 */
final class NativeFileReader implements FileReader
{
    public function getFileContent(string $filePath): ?string
    {
        if (!@\file_exists($filePath)) {
            return null;
        }

        $fileContent = @\file_get_contents($filePath);

        if ($fileContent === false) {
            return null;
        }

        return \trim($fileContent);
    }
}
