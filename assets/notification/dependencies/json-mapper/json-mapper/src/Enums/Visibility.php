<?php
/**
 * @license MIT
 *
 * Modified by bracketspace on 02-October-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */

declare(strict_types=1);

namespace BracketSpace\Notification\Dependencies\JsonMapper\Enums;

use BracketSpace\Notification\Dependencies\MyCLabs\Enum\Enum;

/**
 * @method static Visibility PUBLIC()
 * @method static Visibility PROTECTED()
 * @method static Visibility PRIVATE()
 *
 * @psalm-immutable
 */
class Visibility extends Enum
{
    private const PUBLIC = 'public';
    private const PROTECTED = 'protected';
    private const PRIVATE = 'private';

    public static function fromReflectionProperty(\ReflectionProperty $property): self
    {
        if ($property->isPublic()) {
            return self::PUBLIC();
        }
        if ($property->isProtected()) {
            return self::PROTECTED();
        }
        return self::PRIVATE();
    }
}
