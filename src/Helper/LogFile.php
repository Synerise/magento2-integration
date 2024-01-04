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
        return $this->getLogDirectoryPath() . DIRECTORY_SEPARATOR . $filename ;
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
