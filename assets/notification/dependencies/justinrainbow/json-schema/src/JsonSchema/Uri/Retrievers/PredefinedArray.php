<?php
/**
 * @license MIT
 *
 * Modified by bracketspace on 02-October-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace BracketSpace\Notification\Dependencies\JsonSchema\Uri\Retrievers;

use BracketSpace\Notification\Dependencies\JsonSchema\Validator;

/**
 * URI retrieved based on a predefined array of schemas
 *
 * @example
 *
 *      $retriever = new PredefinedArray(array(
 *          'http://acme.com/schemas/person#'  => '{ ... }',
 *          'http://acme.com/schemas/address#' => '{ ... }',
 *      ))
 *
 *      $schema = $retriever->retrieve('http://acme.com/schemas/person#');
 */
class PredefinedArray extends AbstractRetriever
{
    /**
     * Contains schemas as URI => JSON
     *
     * @var array
     */
    private $schemas;

    /**
     * Constructor
     *
     * @param array  $schemas
     * @param string $contentType
     */
    public function __construct(array $schemas, $contentType = Validator::SCHEMA_MEDIA_TYPE)
    {
        $this->schemas     = $schemas;
        $this->contentType = $contentType;
    }

    /**
     * {@inheritdoc}
     *
     * @see \BracketSpace\Notification\Dependencies\JsonSchema\Uri\Retrievers\UriRetrieverInterface::retrieve()
     */
    public function retrieve($uri)
    {
        if (!array_key_exists($uri, $this->schemas)) {
            throw new \BracketSpace\Notification\Dependencies\JsonSchema\Exception\ResourceNotFoundException(sprintf(
                'The JSON schema "%s" was not found.',
                $uri
            ));
        }

        return $this->schemas[$uri];
    }
}
