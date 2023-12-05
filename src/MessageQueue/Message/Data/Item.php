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
     * @param int $entity_id
     * @param int $store_id
     * @param int|null $website_id
     * @param int $retries
     */
    public function __construct(string $model, int $entity_id, int $store_id, ?int $website_id = null, int $retries = 0)
    {
        $this->model = $model;
        $this->entityId = $entity_id;
        $this->storeId = $store_id;
        $this->websiteId = $website_id;
        $this->retries = $retries;

        if ($model == Product::MODEL && !$website_id) {
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
