<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Modified by bracketspace on 02-October-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace BracketSpace\Notification\Dependencies\Symfony\Component\Console\Exception;

/**
 * Represents failure to read input from stdin.
 *
 * @author Gabriel Ostrolucký <gabriel.ostrolucky@gmail.com>
 */
class MissingInputException extends RuntimeException implements ExceptionInterface
{
}
