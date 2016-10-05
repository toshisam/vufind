<?php
/**
 * Created by PhpStorm.
 * User: nicolas
 * Date: 29.09.16
 * Time: 17:19
 */

namespace Swissbib\Controller;


use Swissbib\Services\NationalLicence;
use Zend\Mvc\MvcEvent;

class ConsoleController extends BaseController
{
    /**
     * Send the National Licence user list export in .csv format via e-mail.
     */
    public function sendNationalLicenceUsersExportAction() {
        /** @var NationalLicence $nationalLicenceService */
        $nationalLicenceService = $this->getServiceLocator()->get('Swissbib\NationalLicenceService');
        $nationalLicenceService->sendExportEmail();
    }

    public function updateNationalLicenceUserInfoAction() {

        //These lines allow to retrieve the route urls from the controller command
        //http://stackoverflow.com/questions/27295895/how-can-i-create-a-url-in-a-console-controller-in-zf2
        $event  = $this->getEvent();
        $http   = $this->getServiceLocator()->get('HttpRouter');
        $router = $event->setRouter($http);
        $request = new \Zend\Http\Request();
        $request->setUri('');
        $router = $event->getRouter();
        $routeMatch = $router->match($request);

        /** @var NationalLicence $nationalLicenceService */
        $nationalLicenceService = $this->getServiceLocator()->get('Swissbib\NationalLicenceService');
        $nationalLicenceService->checkAndUpdateNationalLicenceUserInfo();
    }
}