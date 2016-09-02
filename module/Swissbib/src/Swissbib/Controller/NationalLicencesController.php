<?php
namespace Swissbib\Controller;
use Swissbib\Services\NationalLicence;
use Swissbib\VuFind\Db\Row\NationalLicenceUser;
use Zend\View\Model\ViewModel;
/**
 * Created by PhpStorm.
 * User: nicolas
 * Date: 26.08.16
 * Time: 09:41
 */
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
        //var_dump(json_encode($_SERVER, true));
        $this->randServer();
        $persistentId                = isset($_SERVER['persistent-id']) ? $_SERVER['persistent-id']: null;
        $homePostalAddress           = isset($_SERVER['homePostalAddress']) ? $_SERVER['homePostalAddress']: null;
        $mobile                      = isset($_SERVER['mobile']) ? $_SERVER['mobile']: null;
        $swissLibraryPersonResidence = isset($_SERVER['swissLibraryPersonResidence']) ?
                                            $_SERVER['swissLibraryPersonResidence'] : null;

        /**  @var NationalLicence $nationalLicenceService */
        $nationalLicenceService = $this->getServiceLocator()->get('NationalLicenceService');

        /** @var NationalLicenceUser $user */
        $user = $nationalLicenceService->getOrCreateNationalLicenceUser($persistentId);

        echo "<p style='color:red'>$persistentId</p>";
        echo "<p style='color:red'>$homePostalAddress</p>";
        echo "<p style='color:red'>$mobile</p>";
        echo "<p style='color:red'>$swissLibraryPersonResidence</p>";

        $isHoomePostalAddressInSwitzerland = $nationalLicenceService->isAddressInSwitzerland($homePostalAddress);
        $isSwissPhoneNumber = $nationalLicenceService->isSwissPhoneNumber($mobile);
        $isNationalLicenceCompliant = $nationalLicenceService->isNationalLicenceCompliant();


        return new ViewModel(
            [
                'swissLibraryPersonResidence' => $swissLibraryPersonResidence,
                'homePostalAddress' => $homePostalAddress,
                'mobile' => $mobile,
                'user' => $user,
                'isSwissPhoneNumber' => $isSwissPhoneNumber,
                'isHoomePostalAddressInSwitzerland' => $isHoomePostalAddressInSwitzerland,
                'isNationalLicenceCompliant' => $isNationalLicenceCompliant
            ]
        );
    }

    public function acceptTermsConditionsAction(){
        /** @var NationalLicence $nationalLicenceService */
        $nationalLicenceService = $this->getServiceLocator()->get('NationalLicenceService');
        try {
            $nationalLicenceService->acceptTermsConditions();
        } catch (\Exception $e) {
            $this->flashMessenger()->setNamespace('error')->addMessage(
                $this->translate($e->getMessage())
            );
        }
        $this->redirect()->toRoute('national-licences');
    }

    /**
     * Send request of the temporary access
     */
    public function activateTemporaryAccessAction(){
        /** @var NationalLicence $nationalLicenceService */
        $nationalLicenceService = $this->getServiceLocator()->get('NationalLicenceService');

        $accessCreatedSuccessfully = false;
        try {
            $accessCreatedSuccessfully = $nationalLicenceService->createTemporaryAccessForUser();
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
    // TODO: TO delete
    protected function randServer(){
        $_SERVER['homePostalAddress'] = $this->rand(array(
            'Route de l\'aurore 10$1700 Fribourg$Switzerland'//,
            //null,
            //'not  verified'
        ));
        $_SERVER['mobile'] = $this->rand(array(
            '+41 793433434',
            '+41 773433434',
            '+41 763433434',
            '+41 743433434',
            '+39 793433434',
            null,
            null
        ));
        $_SERVER['swissLibraryPersonResidence'] = $this->rand(array(
            'CH',
            null,
            'FR'
        ));
    }
    protected function rand($array) {
        return $array[array_rand($array)];
    }
}