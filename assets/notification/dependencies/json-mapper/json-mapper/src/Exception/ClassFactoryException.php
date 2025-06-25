<?php
/**
 * @license MIT
 *
 * Modified by bracketspace on 02-October-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */

declare(strict_types=1);

namespace BracketSpace\Notification\Dependencies\JsonMapper\Exception;

class ClassFactoryException extends \Exception
{
    public static function forDuplicateClassname(string $className): self
    {
        return new self("A factory for $className has already been registered");
    }

    public static function forMissingClassname(string $className): self
    {
        return new self("A factory for $className has not been registered");
    }
}
