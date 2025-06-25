<?php
/**
 * @license MIT
 *
 * Modified by bracketspace on 02-October-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */ declare(strict_types=1);

/*
 * This file is part of Composer.
 *
 * (c) Nils Adermann <naderman@naderman.de>
 *     Jordi Boggiano <j.boggiano@seld.be>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace BracketSpace\Notification\Dependencies\Composer\SelfUpdate;

use BracketSpace\Notification\Dependencies\Composer\Pcre\Preg;

/**
 * @author Jordi Boggiano <j.boggiano@seld.be>
 */
class Keys
{
    public static function fingerprint(string $path): string
    {
        $hash = strtoupper(hash('sha256', Preg::replace('{\s}', '', file_get_contents($path))));

        return implode(' ', [
            substr($hash, 0, 8),
            substr($hash, 8, 8),
            substr($hash, 16, 8),
            substr($hash, 24, 8),
            '', // Extra space
            substr($hash, 32, 8),
            substr($hash, 40, 8),
            substr($hash, 48, 8),
            substr($hash, 56, 8),
        ]);
    }
}
