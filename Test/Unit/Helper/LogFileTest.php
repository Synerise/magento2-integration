<?php
declare(strict_types=1);

namespace Synerise\Integration\Test\Unit\Helper;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use PHPUnit\Framework\TestCase;
use Synerise\Integration\Helper\LogFile;

class LogFileTest extends TestCase
{
    /**
     * @var string
     */
    private $root;

    /**
     * @var LogFile
     */
    private $helper;

    protected function setUp(): void
    {
        $this->root = realpath(sys_get_temp_dir()) . '/synerise-logfile-' . uniqid();
        mkdir($this->root . '/log/synerise/nested', 0777, true);
        file_put_contents($this->root . '/log/synerise/debug.log', 'log');
        file_put_contents($this->root . '/log/synerise/nested/inner.log', 'inner');
        file_put_contents($this->root . '/log/system.log', 'system');
        file_put_contents($this->root . '/secret.php', 'secret');
        symlink($this->root . '/secret.php', $this->root . '/log/synerise/link.log');

        $directoryList = $this->createMock(DirectoryList::class);
        $directoryList->method('getPath')->with(DirectoryList::LOG)->willReturn($this->root . '/log');

        $this->helper = new LogFile($directoryList);
    }

    protected function tearDown(): void
    {
        foreach ([
            '/log/synerise/link.log',
            '/log/synerise/nested/inner.log',
            '/log/synerise/debug.log',
            '/log/system.log',
            '/secret.php',
        ] as $file) {
            if (is_link($this->root . $file) || is_file($this->root . $file)) {
                unlink($this->root . $file);
            }
        }
        rmdir($this->root . '/log/synerise/nested');
        rmdir($this->root . '/log/synerise');
        rmdir($this->root . '/log');
        rmdir($this->root);
    }

    public function testReturnsPathOfExistingLogFile(): void
    {
        $this->assertSame(
            $this->root . '/log/synerise/debug.log',
            $this->helper->getExistingFilePath('debug.log')
        );
    }

    /**
     * @dataProvider invalidFileNameProvider
     */
    public function testRejectsFileNamesWithPathParts(string $fileName): void
    {
        $this->assertFalse($this->helper->isValidFileName($fileName));

        $this->expectException(FileSystemException::class);
        $this->helper->getFileAbsolutePath($fileName);
    }

    /**
     * @dataProvider invalidFileNameProvider
     */
    public function testExistingFilePathRejectsFileNamesWithPathParts(string $fileName): void
    {
        $this->expectException(FileSystemException::class);
        $this->helper->getExistingFilePath($fileName);
    }

    public static function invalidFileNameProvider(): array
    {
        return [
            'empty' => [''],
            'current dir' => ['.'],
            'parent dir' => ['..'],
            'parent traversal' => ['../system.log'],
            'deep traversal' => ['../../secret.php'],
            'nested file' => ['nested/inner.log'],
            'absolute path' => ['/etc/passwd'],
            'backslash traversal' => ['..\\system.log'],
            'null byte' => ["debug.log\0.txt"],
        ];
    }

    public function testRejectsMissingFile(): void
    {
        $this->expectException(FileSystemException::class);
        $this->helper->getExistingFilePath('missing.log');
    }

    public function testRejectsDirectory(): void
    {
        $this->expectException(FileSystemException::class);
        $this->helper->getExistingFilePath('nested');
    }

    public function testRejectsSymlinkPointingOutsideLogDirectory(): void
    {
        $this->expectException(FileSystemException::class);
        $this->helper->getExistingFilePath('link.log');
    }
}
