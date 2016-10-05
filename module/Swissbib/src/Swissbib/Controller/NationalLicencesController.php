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
 *
 * @author   Simone Cogno <scogno@snowflake.ch>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 *
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */
namespace Swissbib\Controller;

use Swissbib\Services\NationalLicence;
use Swissbib\VuFind\Db\Row\NationalLicenceUser;
use Zend\Mvc\MvcEvent;
use Zend\View\Model\ViewModel;

class NationalLicencesController extends BaseController
{
    /**
     * @var NationalLicence
     */
    protected $nationalLicenceService;

    /**
     * Constructor.
     *
     * NationalLicencesController constructor.
     *
     * @param NationalLicence $nationalLicenceService
     */
    public function __construct(NationalLicence $nationalLicenceService)
    {
        $this->nationalLicenceService = $nationalLicenceService;
    }

    /**
     * Show the form for became compliant with the Swiss National Licences.
     *
     * @return mixed|ViewModel
     */
    public function indexAction()
    {
        //TODO: Remove the following two lines, they are just for debugging!
        $n = $this->params()->fromQuery('preset');
        //Set a test preset for debugging the view
        $this->presets($n);
        //$str = json_encode($_SERVER, JSON_PRETTY_PRINT);
        //echo "<pre style='color: red;'>$str</pre>";

        // Get user information from the shibboleth attributes
        $uniqueId = isset($_SERVER['uniqueID']) ? $_SERVER['uniqueID'] : null;
        $persistentId =
            isset($_SERVER['persistent-id']) ? $_SERVER['persistent-id'] : null;
        $homePostalAddress =
            isset($_SERVER['homePostalAddress']) ?
                $_SERVER['homePostalAddress'] : null;
        $mobile =
            isset($_SERVER['mobile']) ? $_SERVER['mobile'] : null;
        $homeOrganizationType =
            isset($_SERVER['home_organization_type']) ?
                $_SERVER['home_organization_type'] : null;
        $affiliation =
            isset($_SERVER['affiliation']) ? $_SERVER['affiliation'] : null;
        $swissLibraryPersonResidence =
            isset($_SERVER['swissLibraryPersonResidence']) ?
                $_SERVER['swissLibraryPersonResidence'] : null;

        /** @var NationalLicenceUser $user */
        $user = null;
        try {
            // Create a national licence user liked the the current logged user
            $user = $this->nationalLicenceService
                ->getOrCreateNationalLicenceUserIfNotExists(
                    $persistentId,
                    array(
                        'edu_id' => $uniqueId,
                        'home_organization_type' => $homeOrganizationType,
                        'mobile' => $mobile,
                        'home_postal_address' => $homePostalAddress,
                        'affiliation' => $affiliation,
                        'swiss_library_person_residence' => $swissLibraryPersonResidence,
                    ));
        } catch (\Exception $e) {
            $this->flashMessenger()->setNamespace('error')->addMessage(
                $this->translate($e->getMessage())
            );
        }

        //TODO: Remove this lines, they are just for debugging!
        if (isset($n)) {
            echo "<p style='color:red'>Home postal address: $homePostalAddress</p>";
            echo "<p style='color:red'>Mobile: $mobile</p>";
            echo "<p style='color:red'>Swiss Library Person Residence: " .
                "$swissLibraryPersonResidence</p>";
        }

        // Compute the checks
        $isHomePostalAddressInSwitzerland =
            $this->nationalLicenceService
                ->isAddressInSwitzerland($homePostalAddress);
        $isSwissPhoneNumber =
            $this->nationalLicenceService->isSwissPhoneNumber($mobile);
        $isNationalLicenceCompliant =
            $this->nationalLicenceService->isNationalLicenceCompliant();
        $temporaryAccessValid =
            $this->nationalLicenceService->isTemporaryAccessCurrentlyValid($user);
        $hasAcceptedTermsAndConditions = $user->hasAcceptedTermsAndConditions();
        $hasVerifiedHomePostalAddress =
            $this->nationalLicenceService->hasVerifiedSwissAddress();
        $hasPermanentAccess = $user->hasRequestPermanentAccess();
        $hasAccessToNationalLicenceContent =
            $this->nationalLicenceService->hasAccessToNationalLicenceContent($user);
        //var_dump($hasAccessToNationalLicenceContent);
        if ($hasAccessToNationalLicenceContent) {
            $this->flashMessenger()->addSuccessMessage(
                $this->translate('snl.nationalLicenceCompliant')
            );
        } else {
            $this->flashMessenger()->addErrorMessage(
                $this->translate('snl.youDontHaveAccessToNationalLicencesError')
            );
        }
        $view = new ViewModel(
            [
                'swissLibraryPersonResidence' => $swissLibraryPersonResidence,
                'homePostalAddress' => $homePostalAddress,
                'mobile' => $mobile,
                'user' => $user,
                'isSwissPhoneNumber' => $isSwissPhoneNumber,
                'isHomePostalAddressInSwitzerland' =>
                    $isHomePostalAddressInSwitzerland,
                'isNationalLicenceCompliant' => $isNationalLicenceCompliant,
                'temporaryAccessValid' => $temporaryAccessValid,
                'hasAcceptedTermsAndConditions' => $hasAcceptedTermsAndConditions,
                'hasPermanentAccess' => $hasPermanentAccess,
                'hasVerifiedHomePostalAddress' => $hasVerifiedHomePostalAddress,
            ]
        );

        return $view;
    }

    /**
     * Define some test presets for testing the frontend view
     * TODO: To delete, this is just for debugging.
     *
     * @param int $n
     */
    protected function presets($n = 0)
    {
        switch ($n) {
            case 1:
                $_SERVER['homePostalAddress'] =
                    'Roswiesenstrasse 100$8051 Zürich$Switzerland';
                $_SERVER['mobile'] = '+41 793433434';
                $_SERVER['swissLibraryPersonResidence'] = 'CH';
                break;
            case 2:
                $_SERVER['homePostalAddress'] =
                    'Theobalds Road 29$WC2N London$England';
                $_SERVER['mobile'] = '+44 743433434';
                $_SERVER['swissLibraryPersonResidence'] = 'EN';
                break;
            case 3:
                $_SERVER['homePostalAddress'] = null;
                $_SERVER['mobile'] = '+41 793433434';
                $_SERVER['swissLibraryPersonResidence'] = null;
                break;
            case 4:
                $_SERVER['homePostalAddress'] = null;
                $_SERVER['mobile'] = null;
                $_SERVER['swissLibraryPersonResidence'] = null;
                break;
            case 5:
                $_SERVER['homePostalAddress'] =
                    'Roswiesenstrasse 100$8051 Zürich$Switzerland';
                $_SERVER['mobile'] = null;
                $_SERVER['swissLibraryPersonResidence'] = null;
                break;
            default:

        }
    }

    /**
     * Method called before every action. It checks if the user is authenticated
     * and it redirects it to the login page otherwise.
     *
     * @param MvcEvent $e
     *
     * @return mixed|\Zend\Http\Response
     */
    public function onDispatch(MvcEvent $e)
    {
        $account = $this->getAuthManager();

        if (false === $account->isLoggedIn()) {
            $this->forceLogin(false);

            return $this->redirect()->toRoute('myresearch-home');
        } else {
            return parent::onDispatch($e);
        }
    }

    /**
     * Called when user click on the accept terms and conditions checkbox.
     * This information will be directly stored in the database.
     */
    public function acceptTermsConditionsAction()
    {
        try {
            $this->nationalLicenceService->acceptTermsConditions();
            //TODO: Update the national compliant flag if compliant
        } catch (\Exception $e) {
            $this->flashMessenger()->setNamespace('error')->addMessage(
                $this->translate($e->getMessage())
            );
        }
        $this->redirect()->toRoute('national-licences');
    }

    /**
     * Send request for the temporary access.
     */
    public function activateTemporaryAccessAction()
    {
        $accessCreatedSuccessfully = false;
        try {
            $accessCreatedSuccessfully = $this->nationalLicenceService
                ->createTemporaryAccessForUser();
        } catch (\Exception $e) {
            $this->flashMessenger()->setNamespace('error')->addMessage(
                $this->translate($e->getMessage())
            );
            $this->redirect()->toRoute('national-licences');

            return;
        }
        if (!$accessCreatedSuccessfully) {
            $this->flashMessenger()->setNamespace('error')->addMessage(
                $this->translate('snl.wasNotPossibleToCreateTemporaryAccessError')
            );
            $this->redirect()->toRoute('national-licences');

            return;
        }
        $this->flashMessenger()->setNamespace('success')->addMessage(
            $this->translate('snl.yourTemporaryAccessWasCreatedSuccessfully')
        );
        $this->redirect()->toRoute('national-licences');
    }

    /**
     * Set the permanent access for the current user. Internally this will also
     * adds the user to the National Licence Program using the Switch API.
     */
    public function activatePermanentAccessAction()
    {
        try {
            $this->nationalLicenceService->setNationalLicenceCompliantFlag();
            $this->flashMessenger()->setNamespace('success')->addMessage(
                $this->translate('snl.yourRequestPermanentAccessSuccessful')
            );
        } catch (\Exception $e) {
            $this->flashMessenger()->setNamespace('error')->addMessage(
                $this->translate($e->getMessage())
            );
        }
        $this->redirect()->toRoute('national-licences');
    }

    /**
     * Method called when user want to extend his account. The link to access
     * to this function has to be send by e-mail.
     * TODO.
     */
    public function extendAccountAction()
    {
        try {
            $this->nationalLicenceService->extendAccountIfCompliant();
            if ($this->nationalLicenceService->isMessageSet()) {
                $message = $this->nationalLicenceService->getMessage();
                $this->flashMessenger()->setNamespace($message['type'])->addMessage(
                    $this->translate($message['text'])
                );
            }
        } catch (Exception $e) {
            $this->flashMessenger()->setNamespace('success')->addMessage(
                $this->translate('snl.yourRequestPermanentAccessSuccessful')
            );
        }
        //redirect to home page
        $this->redirect()->toRoute('national-licences');
    }
}
