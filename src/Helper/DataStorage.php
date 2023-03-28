<?php

namespace Synerise\Integration\Helper;

class DataStorage
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
     * Gets data from internal cache by key and unset.
     *
     * @param string $key
     * @return mixed|null
     */
    public function getAndUnsetData(string $key)
    {
        $preparedKey = $this->prepareKey($key);
        if(isset($this->data[$preparedKey])){
            $data = $this->data[$preparedKey];
            $this->removeFromData($key);
            return $data;
        }
        return null;
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
     * Unset variable from data
     *
     * @param string $key
     * @return void
     */
    public function removeFromData(string $key)
    {
        $preparedKey = $this->prepareKey($key);
        unset($this->data[$preparedKey]);
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
