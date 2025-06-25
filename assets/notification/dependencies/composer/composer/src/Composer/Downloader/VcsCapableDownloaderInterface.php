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

namespace BracketSpace\Notification\Dependencies\Composer\Downloader;

use BracketSpace\Notification\Dependencies\Composer\Package\PackageInterface;

/**
 * VCS Capable Downloader interface.
 *
 * @author Steve Buzonas <steve@fancyguy.com>
 */
interface VcsCapableDownloaderInterface
{
    /**
     * Gets the VCS Reference for the package at path
     *
     * @param  PackageInterface $package package instance
     * @param  string           $path    package directory
     * @return string|null      reference or null
     */
    public function getVcsReference(PackageInterface $package, string $path): ?string;
}
