<?php

namespace SilverStripe\GraphQLDevTools\Tests;

use SilverStripe\Dev\FunctionalTest;
use SilverStripe\GraphQL\Schema\Storage\CodeGenerationStore;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\GraphQL\Schema\Logger;

class ClearTest extends FunctionalTest
{

    private $originalDirName;
    private $dirName = 'test-graphql-generated';
    private $absDirName = BASE_PATH . DIRECTORY_SEPARATOR . 'test-graphql-generated';

    protected $usesDatabase = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalDirName = CodeGenerationStore::config()->get('dirName');
        Logger::singleton()->setVerbosity(Logger::EMERGENCY);
        CodeGenerationStore::config()->set('dirName', $this->dirName);

        $fs = new Filesystem();
        $fs->mkdir($this->absDirName);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        CodeGenerationStore::config()->set('dirName', $this->originalDirName);
        $fs = new Filesystem();
        if ($fs->exists($this->absDirName)) {
            $fs->remove($this->absDirName);
        }
    }

    public function testClear()
    {
        $fs = new Filesystem();
        $finder = new Finder();
        $finder->in($this->absDirName);

        $this->assertTrue($fs->exists($this->absDirName), 'Test should begin with a fake code gen folder');

        $this->get('dev/graphql/clear');
        $this->assertFalse($finder->hasResults(), 'GraphQL clear should not break on an empty folder');

        $fs->mkdir($this->absDirName . DIRECTORY_SEPARATOR . 'default');
        $this->assertTrue($finder->hasResults(), 'A fake schema folder should have been created');

        $this->get('dev/graphql/clear');
        $this->assertFalse($finder->hasResults(), 'GraphQL clear should have removed the fake schema folder');

        $fs->remove($this->absDirName);
        $this->get('dev/graphql/clear');
        $this->assertFalse($fs->exists($this->absDirName), 'GraphQL clear should not break on a non-existent folder');
    }

}
