<?php
/**
 * @license MIT
 *
 * Modified by bracketspace on 02-October-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */

declare(strict_types=1);

namespace BracketSpace\Notification\Dependencies\JsonMapper\Middleware\Rename;

use BracketSpace\Notification\Dependencies\JsonMapper\JsonMapperInterface;
use BracketSpace\Notification\Dependencies\JsonMapper\Middleware\AbstractMiddleware;
use BracketSpace\Notification\Dependencies\JsonMapper\ValueObjects\PropertyMap;
use BracketSpace\Notification\Dependencies\JsonMapper\Wrapper\ObjectWrapper;

class Rename extends AbstractMiddleware
{
    /** @var Mapping[] */
    private $mapping;

    public function __construct(Mapping ...$mapping)
    {
        $this->mapping = $mapping;
    }

    public function addMapping(string $class, string $from, string $to): void
    {
        $this->mapping[] = new Mapping($class, $from, $to);
    }

    public function handle(
        \stdClass $json,
        ObjectWrapper $object,
        PropertyMap $propertyMap,
        JsonMapperInterface $mapper
    ): void {
        $mapping = \array_filter($this->mapping, static function ($map) use ($object) {
            return $map->getClass() === $object->getName();
        });
        foreach ($mapping as $map) {
            $from = $map->getFrom();
            $to = $map->getTo();

            if (isset($json->$from)) {
                $json->$to = $json->$from;
                unset($json->$from);
            }
        }
    }
}
