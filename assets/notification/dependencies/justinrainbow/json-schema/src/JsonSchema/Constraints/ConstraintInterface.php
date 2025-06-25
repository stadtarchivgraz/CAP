<?php

/*
 * This file is part of the JsonSchema package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Modified by bracketspace on 02-October-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace BracketSpace\Notification\Dependencies\JsonSchema\Constraints;

use BracketSpace\Notification\Dependencies\JsonSchema\Entity\JsonPointer;

/**
 * The Constraints Interface
 *
 * @author Robert Schönthal <seroscho@googlemail.com>
 */
interface ConstraintInterface
{
    /**
     * returns all collected errors
     *
     * @return array
     */
    public function getErrors();

    /**
     * adds errors to this validator
     *
     * @param array $errors
     */
    public function addErrors(array $errors);

    /**
     * adds an error
     *
     * @param JsonPointer|null $path
     * @param string           $message
     * @param string           $constraint the constraint/rule that is broken, e.g.: 'minLength'
     * @param array            $more       more array elements to add to the error
     */
    public function addError(JsonPointer $path = null, $message, $constraint='', array $more = null);

    /**
     * checks if the validator has not raised errors
     *
     * @return bool
     */
    public function isValid();

    /**
     * invokes the validation of an element
     *
     * @abstract
     *
     * @param mixed            $value
     * @param mixed            $schema
     * @param JsonPointer|null $path
     * @param mixed            $i
     *
     * @throws \BracketSpace\Notification\Dependencies\JsonSchema\Exception\ExceptionInterface
     */
    public function check(&$value, $schema = null, JsonPointer $path = null, $i = null);
}
