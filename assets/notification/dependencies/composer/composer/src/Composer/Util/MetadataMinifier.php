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

namespace BracketSpace\Notification\Dependencies\Composer\Util;

@trigger_error('BracketSpace\Notification\Dependencies\Composer\Util\MetadataMinifier is deprecated, use BracketSpace\Notification\Dependencies\Composer\MetadataMinifier\MetadataMinifier from composer/metadata-minifier instead.', E_USER_DEPRECATED);

/**
 * @deprecated Use Composer\MetadataMinifier\MetadataMinifier instead
 */
class MetadataMinifier extends \BracketSpace\Notification\Dependencies\Composer\MetadataMinifier\MetadataMinifier
{
}
