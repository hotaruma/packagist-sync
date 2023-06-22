<?php

declare(strict_types=1);

namespace Hotaruma\PackagistSync\Command;

use Symfony\Component\Console\{Attribute\AsCommand,
    Command\Command,
    Formatter\OutputFormatterStyle,
    Input\InputArgument,
    Input\InputInterface,
    Input\InputOption,
    Output\OutputInterface
};
use Hotaruma\PackagistSync\Exception\SyncPackageServicePackageNotFoundException;
use Hotaruma\PackagistSync\SyncPackageService;
use Throwable;

#[AsCommand(
    name: 'package:sync',
    description: 'Sync package information with Packagist'
)]
class SyncPackageCommand extends Command
{
    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        $this
            ->addArgument('api_token', InputArgument::REQUIRED, 'Packagist API token')
            ->addOption('packagist_username', 'u', InputOption::VALUE_OPTIONAL, 'Packagist username (optional if package name set in composer.json, use vendor part)')
            ->addOption('package_name', 'p', InputOption::VALUE_OPTIONAL, 'Package name (optional if set in composer.json)')
            ->addOption('packagist_domain', 'd', InputOption::VALUE_OPTIONAL, 'Packagist domain', 'packagist.org')
            ->addOption('github_repository_url', 'g', InputOption::VALUE_OPTIONAL, 'Github repository url (optional if package already exist)')
            ->addOption('composer_json_path', 'c', InputOption::VALUE_OPTIONAL, 'Custom composer.json path');
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $redStyle = new OutputFormatterStyle('red');
        $greenStyle = new OutputFormatterStyle('green');
        $yellowStyle = new OutputFormatterStyle('yellow');

        $output->getFormatter()->setStyle('red', $redStyle);
        $output->getFormatter()->setStyle('green', $greenStyle);
        $output->getFormatter()->setStyle('yellow', $yellowStyle);

        try {
            $syncPackageService = new SyncPackageService();
            $syncPackageService
                ->setApiToken($input->getArgument('api_token'))
                ->setPackagistUsername($input->getOption('packagist_username') ?: getenv('INPUT_PACKAGIST-USERNAME') ?: null)
                ->setPackageName($input->getOption('package_name') ?: getenv('INPUT_PACKAGE-NAME') ?: null)
                ->setPackagistDomain($input->getOption('packagist_domain') ?: getenv('INPUT_PACKAGIST-DOMAIN') ?: null)
                ->setGithubRepositoryUrl($input->getOption('github_repository_url') ?: getenv('INPUT_GITHUB-REPOSITORY-URL') ?: null)
                ->setComposerJsonPath((getenv('GITHUB_WORKSPACE') ?: getcwd()) . ($input->getOption('composer_json_path') ?: getenv('INPUT_COMPOSER-JSON-PATH')));

            try {
                $output->writeln('<yellow>Trying to update the package...</yellow>');
                $syncPackageService->updatePackage();
            } catch (SyncPackageServicePackageNotFoundException $e) {
                $output->writeln(sprintf("<red>%s</red>", $e->getMessage()));
                $output->writeln('<yellow>Creating a new package...</yellow>');
                $syncPackageService->createPackage();
            }

        } catch (Throwable $e) {

            $output->writeln(sprintf('<red>An error occurred: %s</red>', $e->getMessage()));
            return Command::FAILURE;
        }

        $output->writeln('<green>Package synchronization completed successfully.</green>');
        return Command::SUCCESS;
    }
}
