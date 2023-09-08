<?php
namespace Synerise\Integration\Helper;

use Magento\Framework\App\Filesystem\DirectoryList;

class Log
{
    /**
     * @var DirectoryList
     */
    protected $directoryList;

    public function __construct(
        DirectoryList $directoryList
    ) {
        $this->directoryList = $directoryList;
    }

    public function getLogFileAbsolutePath($filename): string
    {
        return $this->getLogDirectoryPath() . DIRECTORY_SEPARATOR . $filename ;
    }

    public function getLogDirectoryPath(): string
    {
        return $this->directoryList->getPath(DirectoryList::LOG) . DIRECTORY_SEPARATOR . 'synerise';
    }
}