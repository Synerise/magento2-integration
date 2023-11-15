<?php
namespace Synerise\Integration\Model\Queue\Update;

Class Message
{
    /**
     * @var string
     */
    private $model;

    /**
     * @var int
     */
    private $store_id;

    /**
     * @var int
     */
    private $entity_id;

    /**
     * @var int
     */
    private $retries;


    /**
     * @param string $model
     * @param int $store_id
     * @param int $entity_id
     * @param int $retries
     */
    public function __construct(string $model, int $store_id, int $entity_id, int $retries)
    {
        $this->model = $model;
        $this->store_id = $store_id;
        $this->entity_id = $entity_id;
        $this->retries = $retries;
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
    public function getStoreId(): int
    {
        return $this->store_id;
    }

    /**
     * @param int $store_id
     * @return void
     */
    public function setStoreId(int $store_id): void
    {
        $this->store_id = $store_id;
    }

    /**
     * @return int
     */
    public function getEntityId(): int
    {
        return $this->entity_id;
    }

    /**
     * @param int $entity_id
     * @return void
     */
    public function setEntityId(int $entity_id): void
    {
        $this->entity_id = $entity_id;
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
