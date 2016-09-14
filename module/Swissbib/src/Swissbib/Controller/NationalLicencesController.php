<?php
/**
 * Controller of the National Licence page on the Swissbib account.
 *
 * PHP version 5
 *
 * Copyright (C) project swissbib, University Library Basel, Switzerland
 * http://www.swissbib.org  / http://www.swissbib.ch / http://www.ub.unibas.ch
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @category Swissbib_VuFind2
 * @package  Controller
 * @author   Simone Cogno <scogno@snowflake.ch>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */
namespace Swissbib\Controller;
use Swissbib\Services\NationalLicence;
use Swissbib\VuFind\Db\Row\NationalLicenceUser;
use Zend\View\Model\ViewModel;


class NationalLicencesController extends \Swissbib\Controller\BaseController
{
    /**
     * Show the form for became compliant with the Swiss National Licences
     *
     * @return mixed|ViewModel
     */
    public function indexAction($errorKey = null){
        $account = $this->getAuthManager();

        if ($account->isLoggedIn() == false) {
            return $this->forceLogin();
        }

        //TODO: Remove the following two lines, they are just for debugging!
        $n=$this->params()->fromQuery("preset");
        //Set a test preset for debugging the view
        $this->presets($n);

        // Get user information from the shibboleth attributes
        $homePostalAddress              = isset($_SERVER["homePostalAddress"]) ? $_SERVER["homePostalAddress"]: null;
        $mobile                         = isset($_SERVER["mobile"]) ? $_SERVER["mobile"]: null;
        $swissLibraryPersonResidence    = isset($_SERVER["swissLibraryPersonResidence"]) ?
                                            $_SERVER["swissLibraryPersonResidence"] : null;

        /** @var NationalLicence $nationalLicenceService */
        $nationalLicenceService = $this->getServiceLocator()->get("NationalLicenceService");

        /** @var NationalLicenceUser $user */
        $user = null;
        try {
            $user = $nationalLicenceService->getCurrentNationalLicenceUser();
        } catch (Exception $e) {
            $this->flashMessenger()->setNamespace("error")->addMessage(
                $this->translate($e->getMessage())
            );
        }

        //TODO: Remove this lines, they are just for debugging!
        if(isset($n)){
            echo "<p style='color:red'>Home postal address: $homePostalAddress</p>";
            echo "<p style='color:red'>Mobile: $mobile</p>";
            echo "<p style='color:red'>Swiss Library Person Residence: $swissLibraryPersonResidence</p>";
        }

        // Compute the checks
        $isHomePostalAddressInSwitzerland = $nationalLicenceService->isAddressInSwitzerland($homePostalAddress);
        $isSwissPhoneNumber = $nationalLicenceService->isSwissPhoneNumber($mobile);
        $isNationalLicenceCompliant = $nationalLicenceService->isNationalLicenceCompliant();

        return new ViewModel(
            [
                "swissLibraryPersonResidence" => $swissLibraryPersonResidence,
                "homePostalAddress" => $homePostalAddress,
                "mobile" => $mobile,
                "user" => $user,
                "isSwissPhoneNumber" => $isSwissPhoneNumber,
                "isHomePostalAddressInSwitzerland" => $isHomePostalAddressInSwitzerland,
                "isNationalLicenceCompliant" => $isNationalLicenceCompliant
            ]
        );
    }

    /**
     * Define some test presets for testing the frontend view
     * TODO: To delete, this is just for debugging
     * @param int $n
     */
    protected function presets($n = 0){
        switch ($n){
            case 1:
                $_SERVER["homePostalAddress"] = 'Roswiesenstrasse 100$8051 Zürich$Switzerland';
                $_SERVER["mobile"] = "+41 793433434";
                $_SERVER["swissLibraryPersonResidence"] = "CH";
                break;
            case 2:
                $_SERVER["homePostalAddress"] = 'Theobalds Road 29$WC2N London$England';
                $_SERVER["mobile"] = "+44 743433434";
                $_SERVER["swissLibraryPersonResidence"] = "EN";
                break;
            case 3:
                $_SERVER["homePostalAddress"] = null;
                $_SERVER["mobile"] = "+41 793433434";
                $_SERVER["swissLibraryPersonResidence"] = null;
                break;
            case 4:
                $_SERVER["homePostalAddress"] = null;
                $_SERVER["mobile"] = null;
                $_SERVER["swissLibraryPersonResidence"] = null;
                break;
            case 5:
                $_SERVER["homePostalAddress"] = 'Roswiesenstrasse 100$8051 Zürich$Switzerland';
                $_SERVER["mobile"] = null;
                $_SERVER["swissLibraryPersonResidence"] = null;
                break;
            default:

        }
    }

    /**
     * Called when user click on the accept terms and conditions checkbox.
     * This information will be directly stored in the database.
     */
    public function acceptTermsConditionsAction(){
        /** @var NationalLicence $nationalLicenceService */
        $nationalLicenceService = $this->getServiceLocator()->get("NationalLicenceService");
        try {
            $nationalLicenceService->acceptTermsConditions();
            //TODO: Update the national compliant flag if compliant
        } catch (\Exception $e) {
            $this->flashMessenger()->setNamespace("error")->addMessage(
                $this->translate($e->getMessage())
            );
        }
        $this->redirect()->toRoute("national-licences");
    }

    /**
     * Send request for the temporary access
     */
    public function activateTemporaryAccessAction(){
        /** @var NationalLicence $nationalLicenceService */
        $nationalLicenceService = $this->getServiceLocator()->get("NationalLicenceService");

        $accessCreatedSuccessfully = false;
        try {
            $accessCreatedSuccessfully = $nationalLicenceService->createTemporaryAccessForUser();
        } catch (\Exception $e) {
            $this->flashMessenger()->setNamespace("error")->addMessage(
                $this->translate($e->getMessage())
            );
            $this->redirect()->toRoute("national-licences");
            return;
        }
        if (!$accessCreatedSuccessfully) {
            $this->flashMessenger()->setNamespace("error")->addMessage(
                $this->translate("snl.wasNotPossibleToCreateTemporaryAccessError")
            );
            $this->redirect()->toRoute("national-licences");
            return;
        }
        $this->flashMessenger()->setNamespace("success")->addMessage(
            $this->translate("snl.yourTemporaryAccessWasCreatedSuccessfully")
        );
        $this->redirect()->toRoute("national-licences");
    }


}