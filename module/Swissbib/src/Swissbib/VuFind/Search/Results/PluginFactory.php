<?php
namespace Swissbib\VuFind\Search\Results;

use Zend\ServiceManager\ServiceLocatorInterface;

use VuFind\Search\Results\PluginFactory as VuFindResultsPluginFactory;

use Swissbib\VuFind\Search\Helper\ExtendedSolrFactoryHelper;

/**
 * Class PluginFactory
 *
 * @package Swissbib\Search\Results
 */
class PluginFactory extends VuFindResultsPluginFactory
{
    /**
     * @inheritDoc
     */
    public function canCreateServiceWithName(ServiceLocatorInterface $serviceLocator, $name, $requestedName)
    {
        /** @var ExtendedSolrFactoryHelper $extendedTargetHelper */
        $extendedTargetHelper    = $serviceLocator->getServiceLocator()->get('Swissbib\ExtendedSolrFactoryHelper');
        $this->defaultNamespace    = $extendedTargetHelper->getNamespace($name, $requestedName);

        return parent::canCreateServiceWithName($serviceLocator, $name, $requestedName);
    }
}
