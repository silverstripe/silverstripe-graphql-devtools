<?php

namespace SilverStripe\GraphQLDevTools;

use SilverStripe\Control\Controller as BaseController;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\GraphQL\Controller as GraphQLController;
use SilverStripe\Control\Director;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Injector\InjectorNotFoundException;
use SilverStripe\Core\Path;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\Security\SecurityToken;

class Controller extends BaseController
{
    /**
     * @var string
     * @config
     */
    private static $default_schema = 'default';

    /**
     * @var array
     * @config
     */
    private static $schemas = [];

    /**
     * @var string
     */
    protected $template = 'DevTools';

    public function index(HTTPRequest $request)
    {
        $routes = $this->getRoutes();
        $json = null;
        if (sizeof($routes) > 1) {
            $tabs = [];
            foreach ($routes as $route) {
                $tabs[] = [
                    'endpoint' => Director::absoluteURL($route),
                    'query' => '',
                    'name' => $route,
                    'headers' => [
                        'X-CSRF-TOKEN' => SecurityToken::inst()->getValue(),
                    ]
                ];
            }

            $json = json_encode($tabs);
        }

        return [
            'Endpoint' => sizeof($routes) === 1 ? $routes[0] : null,
            'TabsJSON' => $json,
        ];
    }

    private function getRoutes(): array
    {
        $schemaOverride = $this->getRequest()->getVar('schema');
        if ($schemaOverride) {
            $schemas = [$schemaOverride];
        } else {
            $schemas = $this->config()->get('schemas');
        }
        $routes = $this->findAvailableRoutes($schemas);
        $defaultSchema = $this->config()->get('default_schema');
        $defaultRoute = $routes[$defaultSchema] ?? null;
        $allRoutes = array_values($routes);
        if (!$defaultRoute) {
            if (sizeof($allRoutes) === 1) {
                $defaultRoute = $allRoutes[0];
            } else {
                throw new \RuntimeException(
                    "Could not find your default schema '$defaultSchema'. You will need to add one
                to the SilverStripe\Control\Director.rules config setting."
                );
            }
        }

        array_unshift($allRoutes, $defaultRoute);
        $uniqueRoutes = array_unique($allRoutes);
        return array_map(function ($route) {
            return Path::join(Director::baseURL(), $route);
        }, $uniqueRoutes);
    }
    /**
     * Find all available graphql routes
     * @param array|string $schemas
     * @return array
     */
    protected function findAvailableRoutes($schemas = []): array
    {
        $routes = [];
        $rules = Director::config()->get('rules');

        foreach ($rules as $pattern => $controllerInfo) {
            $routeClass = (is_string($controllerInfo)) ? $controllerInfo : $controllerInfo['Controller'];
            $explicitSchema = $controllerInfo['Schema'] ?? null;
            if ($explicitSchema) {
                if ($schemas === '*' || in_array($explicitSchema, $schemas)) {
                    $routes[$explicitSchema] = Path::normalise($pattern, true);
                }
                continue;
            }
            try {
                $routeController = Injector::inst()->get($routeClass);
                if ($routeController instanceof GraphQLController) {
                    $schemaKey = class_exists(Schema::class)
                        ? $routeController->getSchemaKey()
                        : $routeController->getManager()->getSchemaKey();
                    if ($schemas === '*' || in_array($schemaKey, $schemas)) {
                        $routes[$schemaKey] = Path::normalise($pattern, true);
                    }
                }
            } catch (InjectorNotFoundException $ex) {
            }

        }
        return $routes;
    }
}
