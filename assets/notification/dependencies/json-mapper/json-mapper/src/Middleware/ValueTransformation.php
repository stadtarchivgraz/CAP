<?php
/**
 * @license MIT
 *
 * Modified by bracketspace on 02-October-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */

declare(strict_types=1);

namespace BracketSpace\Notification\Dependencies\JsonMapper\Middleware;

use BracketSpace\Notification\Dependencies\JsonMapper\JsonMapperInterface;
use BracketSpace\Notification\Dependencies\JsonMapper\ValueObjects\PropertyMap;
use BracketSpace\Notification\Dependencies\JsonMapper\Wrapper\ObjectWrapper;

class ValueTransformation extends AbstractMiddleware
{
    /** @var callable */
    private $mapFunction;

    /** @var bool */
    private $includeKey;

    public function __construct(callable $mapFunction, bool $includeKey = false)
    {
        $this->mapFunction = $mapFunction;
        $this->includeKey = $includeKey;
    }

    public function handle(
        \stdClass $json,
        ObjectWrapper $object,
        PropertyMap $propertyMap,
        JsonMapperInterface $mapper
    ): void {
        $mapFunction = $this->mapFunction;

        foreach ((array) $json as $key => $value) {
            if ($this->includeKey) {
                $json->$key = $mapFunction($key, $value);
                continue;
            }

            $json->$key = $mapFunction($value);
        }
    }
}
