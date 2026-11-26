<?php
namespace Synerise\Integration\Helper;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;

class LogFile
{
    /**
     * @var DirectoryList
     */
    protected $directoryList;

    /**
     * @param DirectoryList $directoryList
     */
    public function __construct(
        DirectoryList $directoryList
    ) {
        $this->directoryList = $directoryList;
    }

    /**
     * Get log file absolute path
     *
     * @param string $filename
     * @return string
     * @throws FileSystemException
     */
    public function getFileAbsolutePath(string $filename): string
    {
        if (!$this->isValidFileName($filename)) {
            throw new FileSystemException(__('Invalid log file name.'));
        }

        return $this->getLogDirectoryPath() . DIRECTORY_SEPARATOR . $filename;
    }

    /**
     * Get absolute path of an existing log file located directly in the log directory
     *
     * @param string $filename
     * @return string
     * @throws FileSystemException
     */
    public function getExistingFilePath(string $filename): string
    {
        // phpcs:disable Magento2.Functions.DiscouragedFunction
        $path = realpath($this->getFileAbsolutePath($filename));
        $directory = realpath($this->getLogDirectoryPath());

        if ($path === false || $directory === false || dirname($path) !== $directory || !is_file($path)) {
            throw new FileSystemException(__('Log file not found.'));
        }
        // phpcs:enable Magento2.Functions.DiscouragedFunction

        return $path;
    }

    /**
     * Check whether the value is a bare file name, without any path parts
     *
     * @param string $filename
     * @return bool
     */
    public function isValidFileName(string $filename): bool
    {
        return $filename !== ''
            && $filename !== '.'
            && $filename !== '..'
            && strpbrk($filename, "/\\\0") === false;
    }

    /**
     * Get synerise log directory path
     *
     * @return string
     * @throws FileSystemException
     */
    public function getLogDirectoryPath(): string
    {
        return $this->directoryList->getPath(DirectoryList::LOG) . DIRECTORY_SEPARATOR . 'synerise';
    }
}
