<?php

namespace SilverStripe\GraphQLDevTools;

use SilverStripe\Control\Controller as BaseController;
use SilverStripe\Control\Director;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Injector\InjectorNotFoundException;
use SilverStripe\Forms\DropdownField;
use SilverStripe\ORM\ArrayLib;
use SilverStripe\Security\SecurityToken;
use SilverStripe\View\Requirements;
use SilverStripe\GraphQL\Controller;

class GraphiQLController extends BaseController
{
    /**
     * @var string
     */
    protected $template = 'GraphiQL';

    public function getRouteSwitcher(): DropdownField
    {
        $routes = $this->findAvailableRoutes();
        $defaultRoute = in_array($this->config()->default_route, $routes) ? $this->config()->default_route : $routes[0];
        $route = $this->getRequest()->getVar('endpoint') ?: $defaultRoute;

        // Legacy. Find the first route mapped to the controller.
        if (!$route && !empty($routes)) {
            $route = $routes[0];
        }

        if (!$route) {
            throw new \RuntimeException("There are no routes set up for a GraphQL server. You will need to add one to the SilverStripe\Control\Director.rules config setting.");
        }

        $route = trim($route, '/');
        $values = ArrayLib::valuekey($routes);

        return DropdownField::create('route', '', $values, $route);
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
                if ($routeController instanceof Controller) {
                    $routes[] = $pattern;
                }
            } catch (InjectorNotFoundException $ex) {
            }

        }
        return $routes;
    }
}
