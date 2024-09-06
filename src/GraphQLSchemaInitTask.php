<?php

namespace SilverStripe\GraphQLDevTools;

use Composer\Console\Input\InputOption;
use SilverStripe\Core\Manifest\ModuleManifest;
use SilverStripe\Core\Path;
use SilverStripe\Dev\BuildTask;
use SilverStripe\PolyExecution\PolyOutput;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;

/**
 * A task that initialises a GraphQL 4+ schema with boilerplate config and files.
 */
class GraphQLSchemaInitTask extends BuildTask
{
    private static bool $can_run_in_cli = false;

    protected static string $commandName = 'GraphQLSchemaInitTask';

    protected static string $description = 'Boilerplate setup for a new GraphQL schema';

    protected string $title = 'Initialise a new GraphQL schema';

    private string $appNamespace;

    private string $schemaName = 'default';

    private string $graphqlConfigDir = '_graphql';

    private string $graphqlCodeDir = 'GraphQL';

    private string $endpoint = 'graphql';

    private string $projectDir = '';

    private string $srcDir = 'src';

    private string $perms = '';

    protected function execute(InputInterface $input, PolyOutput $output): int
    {
        $this->projectDir = ModuleManifest::config()->get('project');
        $this->appNamespace = $input->getOption('namespace');
        $this->schemaName = $input->getOption('name');
        $this->graphqlConfigDir = $input->getOption('graphqlConfigDir');
        $this->graphqlCodeDir = $input->getOption('graphqlCodeDir');
        $this->endpoint = $input->getOption('endpoint');
        $this->srcDir = $input->getOption('srcDir');

        if (!$this->appNamespace) {
            $output->writeln('Please provide a base namespace for your app, e.g. <info>--namespace=App</info> or <info>--namespace=MyVendor\MyProject</info>');
            return Command::INVALID;
        }

        $absProjectDir = Path::join(BASE_PATH, $this->projectDir);
        $this->perms = fileperms($absProjectDir);

        $this->createGraphQLConfig($output);
        $this->createProjectConfig($output);
        $this->createResolvers($output);
        return Command::SUCCESS;
    }

    /**
     * Creates the SS config in _config/graphql.yml
     */
    private function createProjectConfig(PolyOutput $output): void
    {
        $absConfigFile = Path::join(BASE_PATH, $this->projectDir, '_config', 'graphql.yml');
        if (file_exists($absConfigFile)) {
            $output->writeln("Config file $absConfigFile already exists. Skipping.");
            return;
        }

        $rulesArr = [
            "    $this->endpoint: '%\$SilverStripe\GraphQL\Controller.$this->schemaName'"
        ];
        // A default schema is required, even though it's empty
        if ($this->schemaName !== 'default') {
            $rulesArr[] = "    default: '%\$SilverStripe\GraphQL\Controller.default'";
        }
        $rules = implode("\n", $rulesArr);

        $extra = '';
        if ($this->schemaName !== 'default') {
            $extra = <<<YAML
            SilverStripe\Core\Injector\Injector:
              SilverStripe\GraphQL\Controller.$this->schemaName:
                class: SilverStripe\GraphQL\Controller
                constructor:
                  schemaKey: $this->schemaName
            YAML;
        }

        $defaultProjectConfig = <<<YAML
        SilverStripe\Control\Director:
          rules:
        $rules

        SilverStripe\GraphQL\Schema\Schema:
          schemas:
            $this->schemaName:
              src:
                - $this->projectDir/$this->graphqlConfigDir

        $extra
        YAML;
        $defaultProjectConfig = trim($defaultProjectConfig) . "\n";
        file_put_contents($absConfigFile, $defaultProjectConfig);
    }

    /**
     * Creates the graphql schema specific config in _graphql/
     */
    private function createGraphQLConfig(PolyOutput $output): void
    {
        $absGraphQLDir = Path::join(BASE_PATH, $this->projectDir, $this->graphqlConfigDir);
        if (is_dir($absGraphQLDir)) {
            $output->writeln("GraphQL config directory already exists. Skipping.");
            return;
        }

        $output->writeln("Creating graphql config directory: $this->graphqlConfigDir");
        mkdir($absGraphQLDir, $this->perms);
        foreach (['models', 'config', 'types', 'queries', 'mutations'] as $file) {
            touch(Path::join($absGraphQLDir, "$file.yml"));
        }

        // config.yml
        $configPath = Path::join($absGraphQLDir, 'config.yml');
        $defaultConfig = <<<YAML
        resolvers:
          - $this->appNamespace\\$this->graphqlCodeDir\\Resolvers
        YAML;
        file_put_contents($configPath, $defaultConfig);

        // models.yml
        $configPath = Path::join($absGraphQLDir, 'models.yml');
        $defaultConfig = <<<YAML
        # This is just an example to get you started. You should change this.
        Page:
          fields: '*'
          operations: '*'
        YAML;
        file_put_contents($configPath, $defaultConfig);
    }

    /**
     * Creates an example resolvers class for autodiscovery in app/src/GraphQL/Resolvers.php
     */
    private function createResolvers(PolyOutput $output): void
    {
        $absSrcDir = Path::join(BASE_PATH, $this->projectDir, $this->srcDir);
        $absGraphQLCodeDir = Path::join($absSrcDir, $this->graphqlCodeDir);
        $graphqlNamespace = implode('\\', [
            $this->appNamespace,
            str_replace('/', '\\', $this->graphqlCodeDir)
        ]);
        if (is_dir($absGraphQLCodeDir)) {
            $output->writeln("GraphQL code dir $this->graphqlCodeDir already exists. Skipping");
            return;
        }

        $output->writeln("Creating resolvers class in $graphqlNamespace");
        mkdir($absGraphQLCodeDir, $this->perms, true);
        $resolverFile = Path::join($absGraphQLCodeDir, 'Resolvers.php');
        $moreInfo = 'https://docs.silverstripe.org/en/developer_guides/graphql/'
            . 'working_with_generic_types/resolver_discovery/#the-resolver-discovery-pattern';
        $resolverCode = <<<PHP
        <?php

        namespace $graphqlNamespace;

        /**
         * Use this class to define custom resolvers. Static functions in this class
         * matching the pattern resolve<FieldName> or resolve<TypeNameFieldName>
         * will be automatically assigned to their respective fields.
         *
         * More information: $moreInfo
         */
        class Resolvers
        {
            public static function resolveMyQuery(\$obj, array \$args, array \$context): array
            {
                // Return the result of query { myQuery { ... } }
                return [
                    'lorem' => 'ipsum'
                ];
            }
        }

        PHP;
        file_put_contents($resolverFile, $resolverCode);
    }

    public function getOptions(): array
    {
        return [
            new InputOption('namespace', null, InputOption::VALUE_REQUIRED, 'The root namespace (required)'),
            new InputOption('name', null, InputOption::VALUE_REQUIRED, 'The name of the schema', 'default'),
            new InputOption('graphqlConfigDir', null, InputOption::VALUE_REQUIRED, 'The folder where the flushless graphql config files will go', '_graphql'),
            new InputOption(
                'graphqlCodeDir',
                null,
                InputOption::VALUE_REQUIRED,
                'The subfolder of src/ where your GraphQL code (the resolver class) will go. Follows PSR-4 based on the namespace argument',
                'GraphQL'
            ),
            new InputOption('endpoint', null, InputOption::VALUE_REQUIRED, 'The endpoint to use for the schema', 'graphql'),
            new InputOption('srcDir', null, InputOption::VALUE_REQUIRED, 'The subfolder of the project directory where the src code lives', 'src'),
        ];
    }

    public static function getHelp(): string
    {
        return <<<TXT
        This task executes a lot of the boilerplate required to build a new GraphQL schema. It will
        generate a few files in your project directory. Any files that already exist will not be
        overwritten. The task can be run multiple times and is non-destructive.
        TXT;
    }
}
