<?php

namespace Synerise\Integration\Helper\Tracking;

class UuidGenerator
{
    protected const NAMESPACE = 'ea1c3a9d-64a6-45d0-a70c-d2a055f350d3';

    /**
     * Generate uuid by email
     *
     * @param string $email
     * @return string
     */
    public function generateByEmail(string $email): string
    {
        return (string) \Ramsey\Uuid\Uuid::uuid5(self::NAMESPACE, $email);
    }
}
