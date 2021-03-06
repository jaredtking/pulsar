<?php

namespace Pulsar\Validation;

use Pulsar\Interfaces\ValidationRuleInterface;
use Pulsar\Model;

/**
 * Validates a boolean value.
 */
class Boolean implements ValidationRuleInterface
{
    public function validate(&$value, array $options, Model $model): bool
    {
        $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);

        return true;
    }
}
