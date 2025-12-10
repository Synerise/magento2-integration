<?php

namespace Synerise\Integration\Search\SearchRequest;

use Magento\Framework\Api\AbstractSimpleObject;

class Filters extends AbstractSimpleObject
{
    public $expression = [
        'eq' => '%s == "%s"',
        'in' => '%s IN [%s]',
        'is' => '%s IS %s',
        'from' => '%s >= %s',
        'to' => '%s <= %s'
    ];

    public function __toString(): string
    {
        $formatted = [];
        foreach($this->_data as $field => $filter) {
            foreach($filter as $condition => $value) {
                if (isset($this->expression[$condition])) {
                    if (is_array($value)) {
                        $value = sprintf('"%s"', implode('", "', $value));
                    }
                    $formatted[] = sprintf($this->expression[$condition], $field, $value);
                }
            }
        }
        return implode(' AND ', $formatted);
    }
}