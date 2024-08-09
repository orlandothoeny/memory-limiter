<?php

/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types=1);

namespace MemoryLimiter\Tests\Filesystem;

use MemoryLimiter\Filesystem\NativeFileReader;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(NativeFileReader::class)]
class NativeFileReaderTest extends TestCase
{
    private string $tempFilePath;

    public function setUp(): void
    {
        $this->tempFilePath = \tempnam(\sys_get_temp_dir(), 'NativeFileReaderTest');
    }

    public function tearDown(): void
    {
        \unlink($this->tempFilePath);
    }

    public function testGetFileContent(): void
    {
        \file_put_contents($this->tempFilePath, 'testfile');

        $fileReader = new NativeFileReader();

        self::assertSame(
            'testfile',
            $fileReader->getFileContent($this->tempFilePath),
        );
    }

    public function testGetFileContent_fileContentShouldBeTrimmed(): void
    {
        \file_put_contents($this->tempFilePath, "   testfile\n");

        $fileReader = new NativeFileReader();

        self::assertSame(
            'testfile',
            $fileReader->getFileContent($this->tempFilePath),
        );
    }

    public function testGetFileContent_whenFileDoesNotExist_thenNullShouldBeReturned(): void
    {
        $fileReader = new NativeFileReader();

        self::assertNull(
            $fileReader->getFileContent(\sys_get_temp_dir() . '/doesNotExist'),
        );
    }
}
