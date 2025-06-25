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

namespace BracketSpace\Notification\Dependencies\Composer\Repository;

use BracketSpace\Notification\Dependencies\Composer\Semver\Constraint\ConstraintInterface;
use BracketSpace\Notification\Dependencies\Composer\Advisory\PartialSecurityAdvisory;
use BracketSpace\Notification\Dependencies\Composer\Advisory\SecurityAdvisory;

/**
 * Repositories that allow fetching security advisory data
 *
 * @author Jordi Boggiano <j.boggiano@seld.be>
 * @internal
 */
interface AdvisoryProviderInterface
{
    public function hasSecurityAdvisories(): bool;

    /**
     * @param array<string, ConstraintInterface> $packageConstraintMap Map of package name to constraint (can be MatchAllConstraint to fetch all advisories)
     * @return ($allowPartialAdvisories is true ? array{namesFound: string[], advisories: array<string, array<PartialSecurityAdvisory|SecurityAdvisory>>} : array{namesFound: string[], advisories: array<string, array<SecurityAdvisory>>})
     */
    public function getSecurityAdvisories(array $packageConstraintMap, bool $allowPartialAdvisories = false): array;
}
