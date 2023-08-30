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

    /**
     * @return array
     */
    protected function getLogFiles()
    {
        $path = $this->getLogDirectoryPath();
        return is_dir($path) ? scandir($path) : [];
    }

    /**
     * @param $bytes
     * @param int $precision
     * @return string
     */
    protected function filesizeToReadableString($bytes, $precision = 2)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    /**
     * @return array
     */
    public function buildLogData()
    {
        $maxNumOfLogs = 30;
        $logFileData = [];
        $files = $this->getLogFiles();

        //remove rubbish from array
        array_splice($files, 0, 2);

        //build log data into array
        foreach ($files as $file) {
            $fileAbsolutePath = $this->getLogFileAbsolutePath($file);
            $modTime = filemtime($fileAbsolutePath);

            $data = [
                'name' => $file,
                'filesize' => $this->filesizeToReadableString((filesize($fileAbsolutePath))),
                'modTime' => $modTime,
                'modTimeLong' => date("Y-m-d H:i:s.", $modTime)
            ];

            $logFileData[$file] = $data;

        }

        //sort array by modified time
        usort($logFileData, function ($item1, $item2) {
            return $item2['modTime'] <=> $item1['modTime'];
        });

        //limit the amount of log data $maxNumOfLogs
        $logFileData = array_slice($logFileData, 0, $maxNumOfLogs);

        return $logFileData;
    }
}