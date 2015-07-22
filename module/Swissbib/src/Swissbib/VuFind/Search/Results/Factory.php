<?php

namespace Swissbib\VuFind\Search\Results;

use Swissbib\VuFind\Search\Favorites\Results;

use Zend\ServiceManager\ServiceManager;

class Factory
{
    /**
     * Factory for Favorites results object.
     *
     * @param ServiceManager $sm Service manager.
     *
     * @return Results
     */
    public static function getFavorites(ServiceManager $sm)
    {
        $factory = new PluginFactory();
        $obj = $factory->createServiceWithName($sm, 'favorites', 'Favorites');
        $init = new \ZfcRbac\Initializer\AuthorizationServiceInitializer();
        $init->initialize($obj, $sm);
        return $obj;
    }
}