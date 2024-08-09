<?php

/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types=1);

namespace MemoryLimiter\Tests\Filesystem;

use MemoryLimiter\Filesystem\CachedFileReader;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CachedFileReader::class)]
class CachedFileReaderTest extends TestCase
{
    private string $tempFilePath;

    public function setUp(): void
    {
        $this->tempFilePath = \tempnam(\sys_get_temp_dir(), 'CachedFileReaderTest');
    }

    public function tearDown(): void
    {
        \unlink($this->tempFilePath);
    }

    public function testGetFileContent(): void
    {
        \file_put_contents($this->tempFilePath, 'testfile');

        $fileReader = new CachedFileReader();

        self::assertSame(
            'testfile',
            $fileReader->getFileContent($this->tempFilePath),
        );
    }

    public function testGetFileContent_cachedVersionShouldBeReturned(): void
    {
        \file_put_contents($this->tempFilePath, 'testfile');

        $fileReader = new CachedFileReader();

        self::assertSame(
            'testfile',
            $fileReader->getFileContent($this->tempFilePath),
        );

        \file_put_contents($this->tempFilePath, 'new data');

        self::assertSame(
            'testfile',
            $fileReader->getFileContent($this->tempFilePath),
        );
    }

    public function testGetFileContent_whenCacheIsCleared_thenFileShouldBeReadAgain(): void
    {
        \file_put_contents($this->tempFilePath, 'testfile');

        $fileReader = new CachedFileReader();

        self::assertSame(
            'testfile',
            $fileReader->getFileContent($this->tempFilePath),
        );

        \file_put_contents($this->tempFilePath, 'new data');
        $fileReader->clearCache();

        self::assertSame(
            'new data',
            $fileReader->getFileContent($this->tempFilePath),
        );
    }

    public function testGetFileContent_fileContentShouldBeTrimmed(): void
    {
        \file_put_contents($this->tempFilePath, "   testfile\n");

        $fileReader = new CachedFileReader();

        self::assertSame(
            'testfile',
            $fileReader->getFileContent($this->tempFilePath),
        );
    }

    public function testGetFileContent_whenFileDoesNotExist_thenNullShouldBeReturned(): void
    {
        $fileReader = new CachedFileReader();

        self::assertNull(
            $fileReader->getFileContent(\sys_get_temp_dir() . '/doesNotExist'),
        );
    }
}
