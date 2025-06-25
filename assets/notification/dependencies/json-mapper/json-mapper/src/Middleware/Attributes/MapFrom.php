<?php
/**
 * @license MIT
 *
 * Modified by bracketspace on 02-October-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */

declare(strict_types=1);

namespace BracketSpace\Notification\Dependencies\JsonMapper\Middleware\Attributes;

use Attribute;

#[Attribute]
class MapFrom
{
    /** @var string */
    public $source;

    public function __construct(string $source)
    {
        $this->source = $source;
    }
}
