<?php

namespace SilverStripe\GraphQLDevTools;

use SilverStripe\Control\Controller as BaseController;
use SilverStripe\Control\Director;
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

        $routes = Director::config()->get('rules');
        $route = $this->getRequest()->getVar('endpoint') ?: $this->config()->default_route;

        // Legacy. Find the first route mapped to the controller.
        if (!$route) {
            foreach ($routes as $pattern => $controllerInfo) {
                $routeClass = (is_string($controllerInfo)) ? $controllerInfo : $controllerInfo['Controller'];
                if ($routeClass == Controller::class || is_subclass_of($routeClass, Controller::class)) {
                    $route = $pattern;
                    break;
                }
            }
        }

        if (!$route) {
            throw new \RuntimeException("There are no routes set up for a GraphQL server. You will need to add one to the SilverStripe\Control\Director.rules config setting.");
        }

        $route = trim($route, '/');
        $securityID = Controller::config()->enable_csrf_protection
            ? "'" . SecurityToken::inst()->getValue() . "'"
            : 'null';
        Requirements::customScript(
            <<<JS
var GRAPHQL_ROUTE = '{$route}';
var SECURITY_ID = $securityID;
JS
        );

        Requirements::javascript('silverstripe/graphql-devtools: client/dist/graphiql.js');
    }
}
