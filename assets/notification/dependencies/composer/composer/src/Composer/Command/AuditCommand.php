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

namespace BracketSpace\Notification\Dependencies\Composer\Command;

use BracketSpace\Notification\Dependencies\Composer\Composer;
use BracketSpace\Notification\Dependencies\Composer\Repository\RepositorySet;
use BracketSpace\Notification\Dependencies\Composer\Repository\RepositoryUtils;
use BracketSpace\Notification\Dependencies\Symfony\Component\Console\Input\InputInterface;
use BracketSpace\Notification\Dependencies\Symfony\Component\Console\Output\OutputInterface;
use BracketSpace\Notification\Dependencies\Composer\Package\PackageInterface;
use BracketSpace\Notification\Dependencies\Composer\Repository\InstalledRepository;
use BracketSpace\Notification\Dependencies\Composer\Advisory\Auditor;
use BracketSpace\Notification\Dependencies\Composer\Console\Input\InputOption;

class AuditCommand extends BaseCommand
{
    protected function configure(): void
    {
        $this
            ->setName('audit')
            ->setDescription('Checks for security vulnerability advisories for installed packages')
            ->setDefinition([
                new InputOption('no-dev', null, InputOption::VALUE_NONE, 'Disables auditing of require-dev packages.'),
                new InputOption('format', 'f', InputOption::VALUE_REQUIRED, 'Output format. Must be "table", "plain", "json", or "summary".', Auditor::FORMAT_TABLE, Auditor::FORMATS),
                new InputOption('locked', null, InputOption::VALUE_NONE, 'Audit based on the lock file instead of the installed packages.'),
            ])
            ->setHelp(
                <<<EOT
The <info>audit</info> command checks for security vulnerability advisories for installed packages.

If you do not want to include dev dependencies in the audit you can omit them with --no-dev

Read more at https://getcomposer.org/doc/03-cli.md#audit
EOT
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $composer = $this->requireComposer();
        $packages = $this->getPackages($composer, $input);

        if (count($packages) === 0) {
            $this->getIO()->writeError('No packages - skipping audit.');

            return 0;
        }

        $auditor = new Auditor();
        $repoSet = new RepositorySet();
        foreach ($composer->getRepositoryManager()->getRepositories() as $repo) {
            $repoSet->addRepository($repo);
        }

        $auditConfig = $composer->getConfig()->get('audit');

        return min(255, $auditor->audit($this->getIO(), $repoSet, $packages, $this->getAuditFormat($input, 'format'), false, $auditConfig['ignore'] ?? [], $auditConfig['abandoned'] ?? Auditor::ABANDONED_FAIL));
    }

    /**
     * @return PackageInterface[]
     */
    private function getPackages(Composer $composer, InputInterface $input): array
    {
        if ($input->getOption('locked')) {
            if (!$composer->getLocker()->isLocked()) {
                throw new \UnexpectedValueException('Valid composer.json and composer.lock files are required to run this command with --locked');
            }
            $locker = $composer->getLocker();

            return $locker->getLockedRepository(!$input->getOption('no-dev'))->getPackages();
        }

        $rootPkg = $composer->getPackage();
        $installedRepo = new InstalledRepository([$composer->getRepositoryManager()->getLocalRepository()]);

        if ($input->getOption('no-dev')) {
            return RepositoryUtils::filterRequiredPackages($installedRepo->getPackages(), $rootPkg);
        }

        return $installedRepo->getPackages();
    }
}
