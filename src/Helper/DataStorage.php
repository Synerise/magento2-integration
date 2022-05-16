<?php

namespace Synerise\Integration\Helper;

class DataStorage extends \Magento\Framework\App\Helper\AbstractHelper
{

    private $data = [];

    /**
     * Gets data from internal cache by key.
     *
     * @param string $key
     * @return mixed|null
     */
    public function getData(string $key)
    {
        $preparedKey = $this->prepareKey($key);
        return $this->data[$preparedKey] ?? null;
    }

    /**
     * Add data to internal cache.
     *
     * @param string $key
     * @param $data
     * @return void
     */
    public function setData(string $key, $data)
    {
        $preparedKey = $this->prepareKey($key);
        $this->data[$preparedKey] = $data;
    }

    /**
     * Converts key to lower case and trims.
     *
     * @param string $key
     * @return string
     */
    private function prepareKey(string $key): string
    {
        return mb_strtolower(trim($key));
    }
}
