<?php

namespace SilverStripe\GraphQLDevTools;

use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Manifest\ModuleManifest;
use SilverStripe\Dev\BuildTask;
use SilverStripe\GraphQL\Config\Configuration;
use Symfony\Component\Filesystem\Path;

/**
 * A task that initialises a GraphQL 4+ schema with boilerplate config and files.
 */
class GraphQLSchemaInitTask extends BuildTask
{
    private static $segment = 'GraphQLSchemaInitTask';

    protected $enabled = true;

    protected $title = 'Initialise a new GraphQL schema';

    protected $description = 'Boilerplate setup for a new GraphQL schema';

    private string $appNamespace;

    private string $schemaName = 'default';

    private string $graphqlConfigDir = '_graphql';

    private string $graphqlCodeDir = 'GraphQL';

    private string $endpoint = 'graphql';

    private string $projectDir = '';

    private string $srcDir = 'src';

    private string $perms = '';

    /**
     * @param HTTPRequest $request
     */
    public function run($request)
    {
        if (!Director::is_cli()) {
            echo "This task can only be run from CLI\n";
            return;
        }

        if (!class_exists(Configuration::class)) {
            echo "This task requires GraphQL v4+ to be installed\n";
            return;
        }

        if ($request->getVar('help')) {
            $this->showHelp();
            return;
        }

        $appNamespace = $request->getVar('namespace');

        if (!$appNamespace) {
            $segment = static::$segment;
            echo "Please provide a base namespace for your app, e.g. \"namespace=App\" or \"namespace=MyVendor\MyProject\".\nFor help, run \"dev/tasks/$segment help=1\"\n";
            return;
        }

        $this->appNamespace = $appNamespace;

        $this->projectDir = ModuleManifest::config()->get('project');

        $schemaName = $request->getVar('name');
        if ($schemaName) {
            $this->schemaName = $schemaName;
        }

        $graphqlConfigDir = $request->getVar('graphqlConfigDir');
        if ($graphqlConfigDir) {
            $this->graphqlConfigDir = $graphqlConfigDir;
        }

        $graphqlCodeDir = $request->getVar('graphqlCodeDir');
        if ($graphqlCodeDir) {
            $this->graphqlCodeDir = $graphqlCodeDir;
        }

        $endpoint = $request->getVar('endpoint');
        if ($endpoint) {
            $this->endpoint = $endpoint;
        }

        $srcDir = $request->getVar('srcDir');
        if ($srcDir) {
            $this->srcDir = $srcDir;
        }

        $absProjectDir = Path::join(BASE_PATH, $this->projectDir);
        $this->perms = fileperms($absProjectDir);

        $this->createGraphQLConfig();
        $this->createProjectConfig();
        $this->createResolvers();
    }

    /**
     * Creates the SS config in _config/graphql.yml
     */
    private function createProjectConfig(): void
    {
        $absConfigFile = Path::join(BASE_PATH, $this->projectDir, '_config', 'graphql.yml');
        if (file_exists($absConfigFile)) {
            echo "Config file $absConfigFile already exists. Skipping." . PHP_EOL;
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
    private function createGraphQLConfig(): void
    {
        $absGraphQLDir = Path::join(BASE_PATH, $this->projectDir, $this->graphqlConfigDir);
        if (is_dir($absGraphQLDir)) {
            echo "GraphQL config directory already exists. Skipping." . PHP_EOL;
            return;
        }

        echo "Creating graphql config directory: $this->graphqlConfigDir" . PHP_EOL;
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
    private function createResolvers(): void
    {
        $absSrcDir = Path::join(BASE_PATH, $this->projectDir, $this->srcDir);
        $absGraphQLCodeDir = Path::join($absSrcDir, $this->graphqlCodeDir);
        $graphqlNamespace = implode('\\', [
            $this->appNamespace,
            str_replace('/', '\\', $this->graphqlCodeDir)
        ]);
        if (is_dir($absGraphQLCodeDir)) {
            echo "GraphQL code dir $this->graphqlCodeDir already exists. Skipping" . PHP_EOL;
            return;
        }

        echo "Creating resolvers class in $graphqlNamespace" . PHP_EOL;
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

    /**
     * Outputs help text to the console
     */
    private function showHelp(): void
    {
        $segment = static::$segment;
        echo <<<TXT

        ****
        This task executes a lot of the boilerplate required to build a new GraphQL schema. It will
        generate a few files in your project directory. Any files that already exist will not be
        overwritten. The task can be run multiple times and is non-destructive.
        ****

        -- Example:

        $ vendor/bin/sake dev/tasks/$segment namespace="MyAgency\MyApp"

        -- Arguments:

        namespace
        The root namespace. Required.

        name
        The name of the schema. Default: "default" (optional)

        graphqlConfigDir
        The folder where the flushless graphql config files will go. Default: "_graphql" (optional)

        graphqlCodeDir
        The subfolder of src/ where your GraphQL code (the resolver class) will go. Follows PSR-4 based on the namespace argument. Default: "GraphQL". (optional)

        endpoint
        The endpoint to use for the schema. Default: "graphql" (optional)

        srcDir
        The subfolder of the project directory where the src code lives. Default: "src" (optional)

        TXT;
    }
}
