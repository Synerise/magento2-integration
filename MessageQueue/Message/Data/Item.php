<?php
namespace Synerise\Integration\MessageQueue\Message\Data;

use InvalidArgumentException;
use Synerise\Integration\SyneriseApi\Sender\Data\Product;

class Item
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
     * Get model name
     *
     * @return string
     */
    public function getModel(): string
    {
        return $this->model;
    }

    /**
     * Set model name
     *
     * @param string $model
     * @return void
     */
    public function setModel(string $model): void
    {
        $this->model = $model;
    }

    /**
     * Get entity ID
     *
     * @return int
     */
    public function getEntityId(): int
    {
        return $this->entityId;
    }

    /**
     * Set entity ID
     *
     * @param int $entityId
     * @return void
     */
    public function setEntityId(int $entityId): void
    {
        $this->entityId = $entityId;
    }

    /**
     * Get store ID
     *
     * @return int
     */
    public function getStoreId(): int
    {
        return $this->storeId;
    }

    /**
     * Set store ID
     *
     * @param int $storeId
     * @return void
     */
    public function setStoreId(int $storeId): void
    {
        $this->storeId = $storeId;
    }

    /**
     * Get website ID
     *
     * @return int|null
     */
    public function getWebsiteId(): ?int
    {
        return $this->websiteId;
    }

    /**
     * Set website ID
     *
     * @param int $websiteId
     * @return void
     */
    public function setWebsiteId(int $websiteId): void
    {
        $this->websiteId = $websiteId;
    }

    /**
     * Get retries count
     *
     * @return int
     */
    public function getRetries(): int
    {
        return $this->retries;
    }

    /**
     * Set retires count
     *
     * @param int $retries
     * @return void
     */
    public function setRetries(int $retries): void
    {
        $this->retries = $retries;
    }
}
