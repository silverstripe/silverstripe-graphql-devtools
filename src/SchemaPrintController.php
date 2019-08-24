<?php


namespace SilverStripe\GraphQLDevTools;


use GraphQL\Utils\SchemaPrinter;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\GraphQL\Manager;

class SchemaPrintController extends Controller
{

    public function index()
    {
        $key = $this->getRequest()->getVar('schema') ?: 'default';
        $manager = Injector::inst()->createWithArgs(Manager::class, [$key]);
        $manager->configure();
        $schema = $manager->schema();
        $content = SchemaPrinter::doPrint($schema);

        return new HTTPResponse("<pre>$content</pre>", 200);
    }

    public function Link($action = null)
    {
        return 'dev/schemaprint';
    }
}
