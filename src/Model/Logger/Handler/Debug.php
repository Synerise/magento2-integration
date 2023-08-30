<?php

namespace Synerise\Integration\Model\Logger\Handler;

use Magento\Framework\Filesystem\DriverInterface;
use Magento\Framework\Logger\Handler\Base;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

class Debug extends Base
{
    /**
     * @var TimezoneInterface
     */
    private $timezone;

    /**
     * @param DriverInterface $filesystem
     * @param TimezoneInterface $timezone
     * @param string|null $filePath
     */
    public function __construct(
        DriverInterface $filesystem,
        TimezoneInterface $timezone,
        ?string $filePath = null
    ) {
        $this->timezone = $timezone;

        $fileName = '/var/log/synerise/debug-'.$this->timezone->date()->format('Y-m-d').'.log';

        parent::__construct($filesystem, $filePath, $fileName);
    }
}