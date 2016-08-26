<?php
namespace Swissbib\Controller;
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
    public function indexAction(){
        $account = $this->getAuthManager();

        if ($account->isLoggedIn() == false) {
            return $this->forceLogin();
        }

        $shib = $this->getConfig()->Shibboleth;
        $swissLibraryPersonResidence = $this->getRequest()->getServer()->get('homeAddress');
        $swissLibraryPersonResidencePossibleValues = array(
            'CH',
            null,
            'FR'
        );
        $swissLibraryPersonResidence = $swissLibraryPersonResidencePossibleValues[array_rand($swissLibraryPersonResidencePossibleValues)];
        /**var_dump($this->getRequest()->getServer()->get('REDIRECT_homeOrganization'));
        var_dump($this->getRequest()->getServer()->get('homeOrganization'));
        var_dump($this->getRequest()->getServer()->get('homeOrganizationType'));
        var_dump($this->getRequest()->getServer()->get('affiliation'));
        var_dump($this->getRequest()->getServer()->get('gender'));
        var_dump($this->getRequest()->getServer()->get('dateOfBirth'));
        var_dump($this->getRequest()->getServer()->get('uniqueID'));
        var_dump($this->getRequest()->getServer()->get('homePostalAddress'));*/

        $table = $this->getTable('\\Swissbib\\VuFind\\Db\\Table\\SwissNationalLicences');

        $table->createSwissNationalLicenceRow(
            [
                'condition_accepted' => true,
                'request_swiss_mobile_phone' => true
            ]
        );

        if(!$swissLibraryPersonResidence)
            ;// Tells the use to fill homePostalAddress in thr Swiss Eduid login
        if($swissLibraryPersonResidence !== 'CH')
            echo  "<p> not in ch </p>";
        ; //Tells the use that this service is only for swiss residents
        $homePostalAddress = $this->getRequest()->getServer()->get('homePostalAddress');
        $homePostalAddressPossibleValues = array(
            'verified',
            null,
            'not  verified'
        );
        $homePostalAddress = $homePostalAddressPossibleValues[array_rand($homePostalAddressPossibleValues)];
        if($homePostalAddress === 'verified')
            ; // Allow user to reqst a verification -> call the SWITCH API for this

        //echo $swissLibraryPersonResidence;
        //echo $homePostalAddress;
        return new ViewModel(
            [
                'swissLibraryPersonResidence' => $swissLibraryPersonResidence,
                'homePostalAddress' => $homePostalAddress
            ]
        );
    }
}