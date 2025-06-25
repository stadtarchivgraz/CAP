<?php

/*
 * This file is part of the JsonSchema package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Modified by bracketspace on 02-October-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace BracketSpace\Notification\Dependencies\JsonSchema;

/**
 * @package JsonSchema
 */
interface UriRetrieverInterface
{
    /**
     * Retrieve a URI
     *
     * @param string      $uri     JSON Schema URI
     * @param null|string $baseUri
     *
     * @return object JSON Schema contents
     */
    public function retrieve($uri, $baseUri = null);
}
