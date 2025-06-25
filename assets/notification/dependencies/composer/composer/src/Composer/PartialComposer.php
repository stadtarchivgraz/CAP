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

namespace BracketSpace\Notification\Dependencies\Composer;

use BracketSpace\Notification\Dependencies\Composer\Package\RootPackageInterface;
use BracketSpace\Notification\Dependencies\Composer\Util\Loop;
use BracketSpace\Notification\Dependencies\Composer\Repository\RepositoryManager;
use BracketSpace\Notification\Dependencies\Composer\Installer\InstallationManager;
use BracketSpace\Notification\Dependencies\Composer\EventDispatcher\EventDispatcher;

/**
 * @author Jordi Boggiano <j.boggiano@seld.be>
 */
class PartialComposer
{
    /**
     * @var RootPackageInterface
     */
    private $package;

    /**
     * @var Loop
     */
    private $loop;

    /**
     * @var Repository\RepositoryManager
     */
    private $repositoryManager;

    /**
     * @var Installer\InstallationManager
     */
    private $installationManager;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var EventDispatcher
     */
    private $eventDispatcher;

    public function setPackage(RootPackageInterface $package): void
    {
        $this->package = $package;
    }

    public function getPackage(): RootPackageInterface
    {
        return $this->package;
    }

    public function setConfig(Config $config): void
    {
        $this->config = $config;
    }

    public function getConfig(): Config
    {
        return $this->config;
    }

    public function setLoop(Loop $loop): void
    {
        $this->loop = $loop;
    }

    public function getLoop(): Loop
    {
        return $this->loop;
    }

    public function setRepositoryManager(RepositoryManager $manager): void
    {
        $this->repositoryManager = $manager;
    }

    public function getRepositoryManager(): RepositoryManager
    {
        return $this->repositoryManager;
    }

    public function setInstallationManager(InstallationManager $manager): void
    {
        $this->installationManager = $manager;
    }

    public function getInstallationManager(): InstallationManager
    {
        return $this->installationManager;
    }

    public function setEventDispatcher(EventDispatcher $eventDispatcher): void
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function getEventDispatcher(): EventDispatcher
    {
        return $this->eventDispatcher;
    }
}
