<?php
namespace Synerise\Integration\MessageQueue\Message\Data;

use InvalidArgumentException;
use Synerise\Integration\MessageQueue\Sender\Data\Product;

Class Item
{
    /**
     * @var string
     */
    private $model;

    /**
     * @var int
     */
    private $entityId;

    /**
     * @var int
     */
    private $storeId;

    /**
     * @var int
     */
    private $websiteId;

    /**
     * @var int
     */
    private $retries;


    /**
     * @param string $model
     * @param int $entityId
     * @param int $storeId
     * @param int|null $websiteId
     * @param int $retries
     */
    public function __construct(string $model, int $entityId, int $storeId, ?int $websiteId = null, int $retries = 0)
    {
        $this->model = $model;
        $this->entityId = $entityId;
        $this->storeId = $storeId;
        $this->websiteId = $websiteId;
        $this->retries = $retries;

        if ($model == Product::MODEL && !$websiteId) {
            throw new InvalidArgumentException('Website id required for Product');
        }
    }

    /**
     * @return string
     */
    public function getModel(): string
    {
        return $this->model;
    }

    /**
     * @param string $model
     * @return void
     */
    public function setModel(string $model): void
    {
        $this->model = $model;
    }

    /**
     * @return int
     */
    public function getEntityId(): int
    {
        return $this->entityId;
    }

    /**
     * @param int $entityId
     * @return void
     */
    public function setEntityId(int $entityId): void
    {
        $this->entityId = $entityId;
    }

    /**
     * @return int
     */
    public function getStoreId(): int
    {
        return $this->storeId;
    }

    /**
     * @param int $storeId
     * @return void
     */
    public function setStoreId(int $storeId): void
    {
        $this->storeId = $storeId;
    }

    /**
     * @return int|null
     */
    public function getWebsiteId(): ?int
    {
        return $this->websiteId;
    }

    /**
     * @param int $websiteId
     * @return void
     */
    public function setWebsiteId(int $websiteId): void
    {
        $this->websiteId = $websiteId;
    }

    /**
     * @return int
     */
    public function getRetries(): int
    {
        return $this->retries;
    }

    /**
     * @param int $retries
     * @return void
     */
    public function setRetries(int $retries): void
    {
        $this->retries = $retries;
    }
}
