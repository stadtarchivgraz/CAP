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

namespace BracketSpace\Notification\Dependencies\Composer\Package\Loader;

use BracketSpace\Notification\Dependencies\Composer\Package\CompletePackage;
use BracketSpace\Notification\Dependencies\Composer\Package\CompleteAliasPackage;
use BracketSpace\Notification\Dependencies\Composer\Package\RootAliasPackage;
use BracketSpace\Notification\Dependencies\Composer\Package\RootPackage;
use BracketSpace\Notification\Dependencies\Composer\Package\BasePackage;

/**
 * Defines a loader that takes an array to create package instances
 *
 * @author Jordi Boggiano <j.boggiano@seld.be>
 */
interface LoaderInterface
{
    /**
     * Converts a package from an array to a real instance
     *
     * @param  mixed[] $config package data
     * @param  string  $class  FQCN to be instantiated
     *
     * @return CompletePackage|CompleteAliasPackage|RootPackage|RootAliasPackage
     *
     * @phpstan-param class-string<CompletePackage|RootPackage> $class
     */
    public function load(array $config, string $class = 'BracketSpace\Notification\Dependencies\Composer\Package\CompletePackage'): BasePackage;
}
