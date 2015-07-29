<?php
namespace Swissbib\Controller;

use Zend\Config\Config;
use Zend\Http\PhpEnvironment\Response;
use Zend\Session\Container as SessionContainer;
use Zend\View\Model\ViewModel;

use VuFind\Controller\SearchController as VuFindSearchController;
use VuFind\Search\Results\PluginManager as VuFindSearchResultsPluginManager;

use Swissbib\VuFind\Search\Results\PluginManager as SwissbibSearchResultsPluginManager;
use Zend\Stdlib\Parameters;

/**
 * @package       Swissbib
 * @subpackage    Controller
 */
class SearchController extends VuFindSearchController
{

    /**
     * @var    String[]   search targets extended by swissbib
     */
    protected $extendedTargets;



    /**
     * Get model for general results view (all tabs, content of active tab only)
     *
     * @return \Zend\View\Model\ViewModel
     */
    public function resultsAction()
    {
        $resultsFacetConfig = $this->getFacetConfig();
        //do not remember FRBR searches because we ant to jump back to the original search
        $type = $this->params()->fromQuery('type');

        if (!empty($type) && $type == "FRBR") {
            $this->rememberSearch = false;
        }

        $resultViewModel = parent::resultsAction();

        if ($resultViewModel instanceof Response) {
            return $resultViewModel;
        }

        $this->layout()->setVariable('resultViewParams', $resultViewModel->getVariable('params'));
        $resultViewModel->setVariable('facetsConfig', $resultsFacetConfig);
        $resultViewModel->setVariable('htmlLayoutClass', 'resultView');

        return $resultViewModel;
    }



    /**
     * Render advanced search
     *
     * @return    ViewModel
     */
    public function advancedAction()
    {
        $viewModel              = parent::advancedAction();
        $viewModel->options     = $this->getServiceLocator()->get('VuFind\SearchOptionsPluginManager')->get($this->searchClassId);
        $results                = $this->getResultsManager()->get($this->searchClassId);

        $params = $results->getParams();
        $requestParams = new Parameters(
            $this->getRequest()->getQuery()->toArray()
            + $this->getRequest()->getPost()->toArray()
        );
        //GH: We need this initialization only to handle personal limit an sort settings for logged in users
        $params->initLimitAdvancedSearch($requestParams);
        $viewModel->setVariable('params', $params);

        return $viewModel;
    }



    /**
     * Get facet config
     *
     * @return    Config
     */
    protected function getFacetConfig()
    {
        return $this->getServiceLocator()->get('VuFind\Config')->get('facets')->get('Results_Settings');
    }
}
