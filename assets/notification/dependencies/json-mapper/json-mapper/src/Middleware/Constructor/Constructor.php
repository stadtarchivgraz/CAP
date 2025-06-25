<?php
/**
 * @license MIT
 *
 * Modified by bracketspace on 02-October-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */

declare(strict_types=1);

namespace BracketSpace\Notification\Dependencies\JsonMapper\Middleware\Constructor;

use BracketSpace\Notification\Dependencies\JsonMapper\Handler\FactoryRegistry;
use BracketSpace\Notification\Dependencies\JsonMapper\Helpers\ScalarCaster;
use BracketSpace\Notification\Dependencies\JsonMapper\JsonMapperInterface;
use BracketSpace\Notification\Dependencies\JsonMapper\Middleware\AbstractMiddleware;
use BracketSpace\Notification\Dependencies\JsonMapper\ValueObjects\PropertyMap;
use BracketSpace\Notification\Dependencies\JsonMapper\Wrapper\ObjectWrapper;

class Constructor extends AbstractMiddleware
{
    /** @var FactoryRegistry */
    private $factoryRegistry;

    public function __construct(FactoryRegistry $factoryRegistry)
    {
        $this->factoryRegistry = $factoryRegistry;
    }

    public function handle(
        \stdClass $json,
        ObjectWrapper $object,
        PropertyMap $propertyMap,
        JsonMapperInterface $mapper
    ): void {
        if ($this->factoryRegistry->hasFactory($object->getName())) {
            return;
        }

        $reflectedConstructor = $object->getReflectedObject()->getConstructor();
        if (\is_null($reflectedConstructor) || $reflectedConstructor->getNumberOfParameters() === 0) {
            return;
        }

        $this->factoryRegistry->addFactory(
            $object->getName(),
            new DefaultFactory(
                $object->getName(),
                $reflectedConstructor,
                $mapper,
                new ScalarCaster(), // @TODO Copy current caster ??
                $this->factoryRegistry
            )
        );
    }
}
