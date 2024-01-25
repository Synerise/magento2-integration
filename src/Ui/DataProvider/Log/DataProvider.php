<?php
namespace Synerise\Integration\Ui\DataProvider\Log;

use Magento\Framework\Api\SortOrder;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Synerise\Integration\Helper\LogFile;

class DataProvider extends AbstractDataProvider
{
    /**
     * @var LogFile
     */
    protected $logFileHelper;

    /**
     * @var int
     */
    protected $limitOffset = 0;

    /**
     * @var int
     */
    protected $limitSize = 10;

    /**
     * @var string
     */
    protected $orderField = 'name';

    /**
     * @var string
     */
    protected $orderDirection = 'desc';

    /**
     * @param LogFile $logFileHelper
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        LogFile $logFileHelper,
        $name,
        $primaryFieldName,
        $requestFieldName,
        array   $meta = [],
        array   $data = []
    ) {
        $this->logFileHelper = $logFileHelper;

        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * @inheritDoc
     */
    public function getData(): array
    {
        $items = [];
        $files = $this->getFiles($this->logFileHelper->getLogDirectoryPath(), $this->getScnadirSortOrder());

        if ($this->getOrderField() == 'filesize') {
            if ($this->getOrderDirection() == 'ASD') {
                usort($files, function ($fileName1, $fileName2) {
                    return $this->filesize($this->logFileHelper->getFileAbsolutePath($fileName1)) <=>
                        $this->filesize($this->logFileHelper->getFileAbsolutePath($fileName2));
                });
            } else {
                usort($files, function ($fileName1, $fileName2) {
                    return $this->filesize($this->logFileHelper->getFileAbsolutePath($fileName2)) <=>
                        $this->filesize($this->logFileHelper->getFileAbsolutePath($fileName1));
                });
            }
        }

        $filesBatch = array_slice($files, $this->getLimitOffset(), $this->getLimitSize());

        foreach ($filesBatch as $file) {
            $fileAbsolutePath = $this->logFileHelper->getFileAbsolutePath($file);

            $data = [
                'name' => $file,
                'filesize' => $this->formatBytes($this->filesize($fileAbsolutePath)),
                'updated_at' => date("Y-m-d H:i:s.", $this->filemtime($fileAbsolutePath)),
            ];

            $items[] = $data;
        }

        return [
            'items' => $items,
            'totalRecords' => count($files)
        ];
    }

    /**
     * @inheritDoc
     */
    public function addOrder($field, $direction)
    {
        $this->orderField = $field;
        $this->orderDirection = $direction;
    }

    /**
     * Get order field
     *
     * @return string
     */
    public function getOrderField(): string
    {
        return $this->orderField;
    }

    /**
     * Get order direction
     *
     * @return string
     */
    public function getOrderDirection(): string
    {
        return $this->orderDirection;
    }

    /**
     * Get order based on scandir
     *
     * @return int
     */
    public function getScnadirSortOrder(): int
    {
        return $this->getOrderDirection() === SortOrder::SORT_ASC ? SCANDIR_SORT_ASCENDING : SCANDIR_SORT_DESCENDING;
    }

    /**
     * Set limit
     *
     * @param int $offset
     * @param int $size
     * @return void
     */
    public function setLimit($offset, $size)
    {
        $this->limitOffset = ($offset-1) * $size;
        $this->limitSize = $size;
    }

    /**
     * Get limit offset
     *
     * @return int
     */
    public function getLimitOffset(): int
    {
        return $this->limitOffset;
    }

    /**
     * Get limit size
     *
     * @return int
     */
    public function getLimitSize(): int
    {
        return $this->limitSize;
    }

    /**
     * Get files
     *
     * @param string $path
     * @param int $sorting_order
     * @return array
     */
    protected function getFiles(string $path, int $sorting_order = SCANDIR_SORT_DESCENDING): array
    {
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        return is_dir($path) ? array_values(array_diff(scandir($path, $sorting_order), ['..', '.'])) : [];
    }

    /**
     * Format bytes
     *
     * @param int $bytes
     * @param int $precision
     * @return string
     */
    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    /**
     * Get file size
     *
     * @param string $fileAbsolutePath
     * @return false|int
     */
    protected function filesize(string $fileAbsolutePath)
    {
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        return filesize($fileAbsolutePath);
    }

    /**
     * Get file modification time
     *
     * @param string $fileAbsolutePath
     * @return false|int
     */
    private function filemtime(string $fileAbsolutePath): ?int
    {
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        return filemtime($fileAbsolutePath);
    }
}
