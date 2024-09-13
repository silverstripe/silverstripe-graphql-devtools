<?php
namespace SilverStripe\GraphQLDevTools;

use Psr\Log\LoggerInterface;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\Deprecation;
use SilverStripe\GraphQL\Schema\Storage\CodeGenerationStore;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

/**
 * @deprecated 1.1.0 Will be replaced with SilverStripe\GraphQLDevTools\SchemaClear
 */
class Clear extends Controller
{
    private static $url_handlers = [
        '' => 'clear',
    ];

    private static $allowed_actions = [
        'clear',
    ];

    public function __construct()
    {
        parent::__construct();
        Deprecation::withNoReplacement(function () {
            Deprecation::notice(
                '1.1.0',
                'Will be replaced with SilverStripe\GraphQLDevTools\SchemaClear',
                Deprecation::SCOPE_CLASS
            );
        });
    }

    public function clear(HTTPRequest $request): void
    {
        $logger = Injector::inst()->get(LoggerInterface::class . '.graphql-build');
        $dirName = CodeGenerationStore::config()->get('dirName');
        $expectedPath = BASE_PATH . DIRECTORY_SEPARATOR . $dirName;
        $fs = new Filesystem();

        $finder = new Finder();
        // Make finder not recursive
        $finder->depth('== 0');

        $logger->info('Clearing GraphQL code generation directory');
        if ($fs->exists($expectedPath)) {
            $logger->info('Directory has been found');
            if ($finder->in($expectedPath)->hasResults()) {
                foreach ($finder as $file) {
                    $logger->info('Removing ' . $file->getFilename());
                    $fs->remove($file->getRealPath());
                }
                $logger->info('Directory now empty');
            } else {
                $logger->info('Directory is already empty.');
            }
        } else {
            $logger->info('Directory was not found. There is nothing to clear');
        }
    }
}
