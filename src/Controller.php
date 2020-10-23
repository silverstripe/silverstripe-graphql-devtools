<?php

namespace SilverStripe\GraphQLDevTools;

use SilverStripe\Control\Controller as BaseController;
use SilverStripe\GraphQL\Controller as GraphQLController;
use SilverStripe\Control\Director;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Injector\InjectorNotFoundException;
use SilverStripe\Core\Path;
use SilverStripe\Security\SecurityToken;

class Controller extends BaseController
{
    private static $default_route = 'graphql';

    /**
     * @var string
     */
    protected $template = 'DevTools';

    public function getTabsJSON(): string
    {
        $routes = $this->findAvailableRoutes();
        $defaultRoute = in_array($this->config()->get('default_route'), $routes)
            ? $this->config()->get('default_route')
            : ($routes[0] ?? null);
        if (!$defaultRoute) {
            throw new \RuntimeException("There are no routes set up for a GraphQL server. You will need to add one to the SilverStripe\Control\Director.rules config setting.");
        }
        array_unshift($routes, $defaultRoute);
        $routes = array_unique($routes);
        $tabs = [];
        foreach ($routes as $route) {
            $tabs[] = [
                'endpoint' => Director::absoluteURL($route),
                'query' => '',
                'name' => '/' . $route,
                'headers' => [
                    'X-CSRF-TOKEN' => SecurityToken::inst()->getValue(),
                ]
            ];
        }

        return json_encode($tabs);
    }

    /**
     * Find all available graphql routes
     * @return string[]
     */
    protected function findAvailableRoutes(): array
    {
        $routes = [];
        $rules = Director::config()->get('rules');

        foreach ($rules as $pattern => $controllerInfo) {
            $routeClass = (is_string($controllerInfo)) ? $controllerInfo : $controllerInfo['Controller'];

            try {
                $routeController = Injector::inst()->get($routeClass);
                if ($routeController instanceof GraphQLController) {
                    $routes[] = Path::normalise($pattern, true);
                }
            } catch (InjectorNotFoundException $ex) {
            }

        }
        return $routes;
    }
}
