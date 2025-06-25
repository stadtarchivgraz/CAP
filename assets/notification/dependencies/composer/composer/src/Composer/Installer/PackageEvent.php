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

namespace BracketSpace\Notification\Dependencies\Composer\Installer;

use BracketSpace\Notification\Dependencies\Composer\Composer;
use BracketSpace\Notification\Dependencies\Composer\IO\IOInterface;
use BracketSpace\Notification\Dependencies\Composer\DependencyResolver\Operation\OperationInterface;
use BracketSpace\Notification\Dependencies\Composer\Repository\RepositoryInterface;
use BracketSpace\Notification\Dependencies\Composer\EventDispatcher\Event;

/**
 * The Package Event.
 *
 * @author Jordi Boggiano <j.boggiano@seld.be>
 */
class PackageEvent extends Event
{
    /**
     * @var Composer
     */
    private $composer;

    /**
     * @var IOInterface
     */
    private $io;

    /**
     * @var bool
     */
    private $devMode;

    /**
     * @var RepositoryInterface
     */
    private $localRepo;

    /**
     * @var OperationInterface[]
     */
    private $operations;

    /**
     * @var OperationInterface The operation instance which is being executed
     */
    private $operation;

    /**
     * Constructor.
     *
     * @param OperationInterface[] $operations
     */
    public function __construct(string $eventName, Composer $composer, IOInterface $io, bool $devMode, RepositoryInterface $localRepo, array $operations, OperationInterface $operation)
    {
        parent::__construct($eventName);

        $this->composer = $composer;
        $this->io = $io;
        $this->devMode = $devMode;
        $this->localRepo = $localRepo;
        $this->operations = $operations;
        $this->operation = $operation;
    }

    public function getComposer(): Composer
    {
        return $this->composer;
    }

    public function getIO(): IOInterface
    {
        return $this->io;
    }

    public function isDevMode(): bool
    {
        return $this->devMode;
    }

    public function getLocalRepo(): RepositoryInterface
    {
        return $this->localRepo;
    }

    /**
     * @return OperationInterface[]
     */
    public function getOperations(): array
    {
        return $this->operations;
    }

    /**
     * Returns the package instance.
     */
    public function getOperation(): OperationInterface
    {
        return $this->operation;
    }
}
