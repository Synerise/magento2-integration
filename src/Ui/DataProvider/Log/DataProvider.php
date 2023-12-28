<?php
namespace Synerise\Integration\Ui\DataProvider\Log;

use Magento\Framework\Api\SortOrder;
use Synerise\Integration\Helper\Log;

class DataProvider extends \Magento\Ui\DataProvider\AbstractDataProvider
{
    /**
     * @var Log
     */
    protected $logHelper;

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

    public function __construct(
        Log $logHelper,
        $name,
        $primaryFieldName,
        $requestFieldName,
        array $meta = [],
        array $data = []
    ) {
        $this->logHelper = $logHelper;

        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        $items = [];
        $files = $this->getFiles($this->logHelper->getLogDirectoryPath(), $this->getScnadirSortOrder());

        if ($this->getOrderField() == 'filesize') {
            if ($this->getOrderDirection() == 'ASD') {
                usort($files, function ($fileName1, $fileName2) {
                    return filesize($this->logHelper->getLogFileAbsolutePath($fileName1)) <=>
                        filesize($this->logHelper->getLogFileAbsolutePath($fileName2));
                });
            } else {
                usort($files, function ($fileName1, $fileName2) {
                    return filesize($this->logHelper->getLogFileAbsolutePath($fileName2)) <=>
                        filesize($this->logHelper->getLogFileAbsolutePath($fileName1));
                });
            }
        }

        $filesBatch = array_slice($files, $this->getLimitOffset(), $this->getLimitSize());

        foreach ($filesBatch as $file) {
            $fileAbsolutePath = $this->logHelper->getLogFileAbsolutePath($file);

            $data = [
                'name' => $file,
                'filesize' => $this->formatBytes((filesize($fileAbsolutePath))),
                'updated_at' => date("Y-m-d H:i:s.", filemtime($fileAbsolutePath)),
            ];

            $items[] = $data;
        }

        return [
            'items' => $items,
            'totalRecords' => count($files)
        ];
    }

    /**
     * self::setOrder() alias
     *
     * @param string $field
     * @param string $direction
     * @return void
     */
    public function addOrder($field, $direction)
    {
        $this->orderField = $field;
        $this->orderDirection = $direction;
    }

    /**
     * @return string
     */
    public function getOrderField(): string
    {
        return $this->orderField;
    }

    /**
     * @return string
     */
    public function getOrderDirection(): string
    {
        return $this->orderDirection;
    }

    /**
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
     * @return int
     */
    public function getLimitOffset(): int
    {
        return $this->limitOffset;
    }

    /**
     * @return int
     */
    public function getLimitSize(): int
    {
        return $this->limitSize;
    }

    /**
     * @param string $path
     * @param int $sorting_order
     * @return array
     */
    protected function getFiles(string $path, int $sorting_order = SCANDIR_SORT_DESCENDING): array
    {
        return is_dir($path) ? array_values(array_diff(scandir($path, $sorting_order), ['..', '.'])) : [];
    }

    /**
     * @param $bytes
     * @param int $precision
     * @return string
     */
    protected function formatBytes($bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
