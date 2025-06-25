<?php
/**
 * @license MIT
 *
 * Modified by bracketspace on 02-October-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */

declare(strict_types=1);

namespace BracketSpace\Notification\Dependencies\JsonMapper\Dto;

/**
 * @psalm-immutable
 */
class NamedMiddleware
{
    /** @var callable */
    private $middleware;
    /** @var string */
    private $name;

    public function __construct(callable $middleware, string $name)
    {
        $this->middleware = $middleware;
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getMiddleware(): callable
    {
        return $this->middleware;
    }
}
