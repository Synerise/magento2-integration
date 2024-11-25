<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Synerise\Integration\Search;

use Magento\Search\Model\SearchEngine\ValidatorInterface;

/**
 * Validate Search engine connection
 */
class Validator implements ValidatorInterface
{
    /**
     * @inheritdoc
     */
    public function validate(): array
    {
        return [];
    }
}
