<?php
namespace SilverStripe\GraphQLDevTools;

use SilverStripe\Dev\Command\DevCommand;
use SilverStripe\GraphQL\Schema\Storage\CodeGenerationStore;
use SilverStripe\PolyExecution\PolyOutput;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class SchemaClear extends DevCommand
{
    protected static string $commandName = 'graphql:clear';

    protected static string $description = 'Clear the GraphQL schema';

    private static array $permissions_for_browser_execution = [
        'CAN_DEV_GRAPHQL',
    ];

    public function getTitle(): string
    {
        return 'GraphQL Schema Clearer';
    }

    protected function execute(InputInterface $input, PolyOutput $output): int
    {
        $dirName = CodeGenerationStore::config()->get('dirName');
        $expectedPath = BASE_PATH . DIRECTORY_SEPARATOR . $dirName;
        $fs = new Filesystem();

        $finder = new Finder();
        // Make finder not recursive
        $finder->depth('== 0');

        if ($fs->exists($expectedPath)) {
            $output->writeln('Directory has been found');
            if ($finder->in($expectedPath)->hasResults()) {
                foreach ($finder as $file) {
                    $output->writeln('Removing ' . $file->getFilename());
                    $fs->remove($file->getRealPath());
                }
                $output->writeln('Directory now empty');
            } else {
                $output->writeln('Directory is already empty.');
            }
        } else {
            $output->writeln('Directory was not found. There is nothing to clear');
        }
        return Command::SUCCESS;
    }

    protected function getHeading(): string
    {
        return 'Clearing GraphQL code generation directory';
    }
}
