<?php

namespace SilverStripe\GraphQLDevTools;

use SilverStripe\Control\Controller as BaseController;
use SilverStripe\Control\Director;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Injector\InjectorNotFoundException;
use SilverStripe\Security\SecurityToken;
use SilverStripe\View\Requirements;
use SilverStripe\GraphQL\Controller;

class GraphiQLController extends BaseController
{
    /**
     * @var string
     */
    protected $template = 'GraphiQL';

    /**
     * Initialise the controller, sanity check, load javascript
     */
    public function init()
    {
        parent::init();

        if (!Director::isDev()) {
            $this->httpError(403, 'The GraphiQL tool is only available in dev mode');
            return;
        }

        $routes = $this->findAvailableRoutes();
        $route = $this->getRequest()->getVar('endpoint') ?: $this->config()->default_route;

        // Legacy. Find the first route mapped to the controller.
        if (!$route && !empty($routes)) {
            $route = $routes[0];
        }

        if (!$route) {
            throw new \RuntimeException("There are no routes set up for a GraphQL server. You will need to add one to the SilverStripe\Control\Director.rules config setting.");
        }

        $route = trim($route, '/');
        $jsonRoutes = json_encode($routes);
        $securityID = Controller::config()->enable_csrf_protection
            ? "'" . SecurityToken::inst()->getValue() . "'"
            : 'null';
        Requirements::customScript(
            <<<JS
var GRAPHQL_ROUTE = '{$route}';
var GRAPHQL_ROUTES = $jsonRoutes;
var SECURITY_ID = $securityID;
JS
        );

        Requirements::javascript('silverstripe/graphql-devtools: client/dist/graphiql.js');
    }

    /**
     * Find all available graphql routes
     * @return string[]
     */
    protected function findAvailableRoutes()
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
