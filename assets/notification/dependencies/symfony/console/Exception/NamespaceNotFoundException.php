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
 * Represents an incorrect namespace typed in the console.
 *
 * @author Pierre du Plessis <pdples@gmail.com>
 */
class NamespaceNotFoundException extends CommandNotFoundException
{
}
