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
        echo __METHOD__;
        /** @var NationalLicence $nationalLicenceService */
        $nationalLicenceService = $this->getServiceLocator()->get('Swissbib\NationalLicenceService');
        $nationalLicenceService->checkAndUpdateNationalLicenceUserInfo();
    }
}