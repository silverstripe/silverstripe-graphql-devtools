<?php

namespace SilverStripe\GraphQLDevTools;

use SilverStripe\Control\Controller as BaseController;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\GraphQL\Controller as GraphQLController;
use SilverStripe\Control\Director;
use SilverStripe\Control\Middleware\RequestHandlerMiddlewareAdapter;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Injector\InjectorNotFoundException;
use SilverStripe\Core\Path;
use SilverStripe\GraphQL\Schema\Schema;
use SilverStripe\Security\SecurityToken;
use SilverStripe\View\Requirements;

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
        $endpoint = sizeof($routes ?? []) === 1 ? $routes[0] : null;
        $csrf = SecurityToken::inst()->getValue();
        $tabs = [];
        if (sizeof($routes ?? []) > 1) {
            foreach ($routes as $route) {
                $tabs[] = [
                    'endpoint' => Director::absoluteURL($route),
                    'query' => '',
                    'name' => $route,
                    'headers' => [
                        'X-CSRF-TOKEN' => $csrf,
                    ]
                ];
            }
        }

        $data = [
            'headers' => [
                'X-CSRF-TOKEN' => $csrf,
            ],
            'endpoint' => $endpoint,
            'settings' => [
                'request.globalHeaders' => [
                    'X-CSRF-TOKEN' => $csrf,
                ],
                'request.credentials' => 'include',
            ],
        ];

        if ($tabs) {
            $data['tabs'] = $tabs;
        }

        $jsonPayload = json_encode($data);

        Requirements::customScript(<<<JS
          window.addEventListener('load', function (event) {
            GraphQLPlayground.init(document.getElementById('root'), $jsonPayload)
          });
        JS
        );

        return [
            'Endpoint' => $endpoint,
            'TabsJSON' => $tabs ? json_encode($tabs): null,
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
        $allRoutes = array_values($routes ?? []);
        if (!$defaultRoute) {
            if (sizeof($allRoutes ?? []) === 1) {
                $defaultRoute = $allRoutes[0];
            } else {
                throw new \RuntimeException(
                    "Could not find your default schema '$defaultSchema'. You will need to add one
                to the SilverStripe\Control\Director.rules config setting."
                );
            }
        }

        array_unshift($allRoutes, $defaultRoute);
        $uniqueRoutes = array_unique($allRoutes ?? []);
        return array_map(function ($route) {
            return Path::join(Director::baseURL(), $route);
        }, $uniqueRoutes ?? []);
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
                if ($schemas === '*' || in_array($explicitSchema, $schemas ?? [])) {
                    $routes[$explicitSchema] = Path::normalise($pattern, true);
                }
                continue;
            }
            try {
                $routeController = Injector::inst()->get($routeClass);

                // Add support for decoration via RequestHandlerMiddlewareAdapter
                if ($routeController instanceof RequestHandlerMiddlewareAdapter) {
                    $routeController = $routeController->getRequestHandler();
                }

                if ($routeController instanceof GraphQLController) {
                    $schemaKey = class_exists(Schema::class)
                        ? $routeController->getSchemaKey()
                        : $routeController->getManager()->getSchemaKey();
                    if ($schemas === '*' || in_array($schemaKey, $schemas ?? [])) {
                        $routes[$schemaKey] = Path::normalise($pattern, true);
                    }
                }
            } catch (InjectorNotFoundException $ex) {
            }
        }
        return $routes;
    }
}
