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

use BracketSpace\Notification\Dependencies\Composer\Json\JsonFile;
use BracketSpace\Notification\Dependencies\Composer\Package\BasePackage;
use BracketSpace\Notification\Dependencies\Composer\Package\CompletePackage;
use BracketSpace\Notification\Dependencies\Composer\Package\CompleteAliasPackage;
use BracketSpace\Notification\Dependencies\Composer\Package\RootPackage;
use BracketSpace\Notification\Dependencies\Composer\Package\RootAliasPackage;

/**
 * @author Konstantin Kudryashiv <ever.zet@gmail.com>
 */
class JsonLoader
{
    /** @var LoaderInterface */
    private $loader;

    public function __construct(LoaderInterface $loader)
    {
        $this->loader = $loader;
    }

    /**
     * @param  string|JsonFile                      $json A filename, json string or JsonFile instance to load the package from
     * @return CompletePackage|CompleteAliasPackage|RootPackage|RootAliasPackage
     */
    public function load($json): BasePackage
    {
        if ($json instanceof JsonFile) {
            $config = $json->read();
        } elseif (file_exists($json)) {
            $config = JsonFile::parseJson(file_get_contents($json), $json);
        } elseif (is_string($json)) {
            $config = JsonFile::parseJson($json);
        } else {
            throw new \InvalidArgumentException(sprintf(
                "JsonLoader: Unknown \$json parameter %s. Please report at https://github.com/composer/composer/issues/new.",
                gettype($json)
            ));
        }

        return $this->loader->load($config);
    }
}
