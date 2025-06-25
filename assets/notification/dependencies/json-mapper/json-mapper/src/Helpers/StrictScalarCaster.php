<?php
/**
 * @license MIT
 *
 * Modified by bracketspace on 02-October-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */

declare(strict_types=1);

namespace BracketSpace\Notification\Dependencies\JsonMapper\Helpers;

use BracketSpace\Notification\Dependencies\JsonMapper\Enums\ScalarType;

class StrictScalarCaster implements IScalarCaster
{
    /** @param $value mixed */
    public function cast(ScalarType $scalarType, $value)
    {
        $type = gettype($value);

        if (! is_string($value) && $scalarType->equals(ScalarType::STRING())) {
            throw new \Exception("Expected type string, type {$type} given");
        }
        if (
            ! is_bool($value) &&
            ($scalarType->equals(ScalarType::BOOLEAN()) || $scalarType->equals(ScalarType::BOOL()))
        ) {
            throw new \Exception("Expected type string, type {$type} given");
        }
        if (
            ! is_int($value) &&
            ($scalarType->equals(ScalarType::INTEGER()) || $scalarType->equals(ScalarType::INT()))
        ) {
            throw new \Exception("Expected type string, type {$type} given");
        }
        if (
            ! is_float($value)
            && ($scalarType->equals(ScalarType::DOUBLE()) || $scalarType->equals(ScalarType::FLOAT()))
        ) {
            throw new \Exception("Expected type string, type {$type} given");
        }

        return $value;
    }
}
