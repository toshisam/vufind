<?php
/**
 * Created by PhpStorm.
 * User: nicolas
 * Date: 30.08.16
 * Time: 17:37
 */

namespace Swissbib\Services;
use Swissbib\Libadmin\Exception\Exception;
use Swissbib\VuFind\Db\Row\NationalLicenceUser;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class NationalLicence implements ServiceLocatorAwareInterface
{
    /**
     * ServiceLocator
     *
     * @var ServiceLocatorInterface
     */
    protected $serviceLocator;

    /**
     * Set serviceManager instance
     *
     * @param ServiceLocatorInterface $serviceLocator ServiceLocatorInterface
     *
     * @return void
     */
    public function setServiceLocator(ServiceLocatorInterface $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
    }

    /**
     * Retrieve serviceManager instance
     *
     * @return ServiceLocatorInterface
     */
    public function getServiceLocator()
    {
        return $this->serviceLocator;
    }


    /**
     * Create a temporary access for the user valid for 14 days. If the user id is not provided
     * the current user in the $_SERVER variable will be used.
     *
     * @param int $persistentId
     * @return bool
     * @throws \Exception
     */
    public function createTemporaryAccessForUser($persistentId = null) {

        /** @var NationalLicenceUser $user */
        $user = $this->getCurrentNationalLicenceUser();

        $this->checkIfUserIsBlocked($user);

        if($user->hasAldreadyRequestedTemporaryAccess()) {
            throw new \Exception("snl.youHaveAlreadyRequestedTemporary");
        }

        if(!$user->hasAcceptedTermsAndConditions()){
            throw new \Exception("snl.plaeseAcceptTermsAndConditions");
        }

        /** @var string $mobile */
        $mobile = $_SERVER['mobile'];

        if (!$mobile) {
            throw new \Exception("snl.youDontHaveMobilePhoneNumeber");
        }

        if(!$this->isSwissPhoneNumber($mobile)) {
            throw new \Exception("snl.mobilePhoneNumberIsNotSwissError".$mobile);
        }

        return $user->setTemporaryAccess();
    }

    /**
     * Persist information if the user accepts the terms and conditions
     */
    public function acceptTermsConditions()
    {
        /** @var NationalLicenceUser $user */
        $user = $this->getCurrentNationalLicenceUser();
        $this->checkIfUserIsBlocked($user);
        $user->setConditionsAccepted(true);
    }

    /**
     * @param null $persistentId
     * @return array|\ArrayObject|null
     * @throws \Exception
     */
    public function getCurrentNationalLicenceUser($persistentId = null)
    {
        // If no id is passed in the parameter take de current user
        if(empty($persistentId) AND isset($_SERVER['persistent-id'])) {
            $persistentId = $_SERVER['persistent-id'];
        }
        if(empty($persistentId)) {
            throw new \Exception("Unable to fetch the user from the database.");
        }
        /** @var \Swissbib\VuFind\Db\Table\NationalLicenceUser $userTable */
        $userTable = $this->getTable('\\Swissbib\\VuFind\\Db\\Table\\NationalLicenceUser');
        return $userTable->getUserByPersistentId($persistentId);
    }

    /**
     * Get a NationalLicenceUser or creates a new one if is not exsisting in the database
     *
     * @param string $persistentId Edu-id persistent id
     * @param array $userFields Array of national licence user fields with their values.
     * @return NationalLicenceUser
     */
    public function getOrCreateNationalLicenceUser($persistentId, $userFields = array())
    {
        /** @var \Swissbib\VuFind\Db\Table\NationalLicenceUser $userTable */
        $userTable = $this->getTable('\\Swissbib\\VuFind\\Db\\Table\\NationalLicenceUser');
        $user = $userTable->getUserByPersistentId($persistentId);
        if(empty($user)) {
            $user = $userTable->createNationalLicenceUserRow($persistentId, $userFields);
        }
        return $user;
    }

    /**
     * Check is it's a swiss phone number
     *
     * @param string $phoneNumber
     * @return bool
     */
    public function isSwissPhoneNumber($phoneNumber){
        if(!$phoneNumber) return false;
        $prefix = substr( $phoneNumber, 0, 6 );
        if ($prefix === "+41 79") return true;
        if ($prefix === "+41 78") return true;
        if ($prefix === "+41 77") return true;
        return false;
    }


    public function isNationalLicenceCompliant()
    {
        $user = $this->getCurrentNationalLicenceUser();
        // Has accepted temrs and conditions
        /** @var NationalLicenceUser $user */
        if (!$user->hasAcceptedTermsAndConditions()) {
           echo "Reason: terms conditions";
           return false;
        }
        // Is not blocked by the administrators
        if ($user->isBlocked()) {
            echo "Reason: blocked";
            return false;
        }
        // Last activity at least in the last 12 months
        if(!$this->hasBeenActiveInLast12Months($user)) {
            echo "Reason: 12 month";
            return false;
        }
        // Has requested a temporary access || Has a verified home postal address
        $hasTemporaryAccess = $user->hasAldreadyRequestedTemporaryAccess() &&
                              $this->isTemporaryAccessCurrentlyValid($user);
        $hasVerifiedSwissAddress = $this->hasVerifiedSwissAddress();

        if(!$hasTemporaryAccess)  echo "Reason: temporary";
        if(!$hasVerifiedSwissAddress)  echo "Reason: verified address";
        return $hasTemporaryAccess || $hasVerifiedSwissAddress ;
    }

    /**
     * Check if the user has been active in the last 12 Months
     *
     * @param NationalLicenceUser $user
     * @return bool
     */
    protected function hasBeenActiveInLast12Months($user) {
        $pastPoint = (new \DateTime())->modify("-12 months");
        if ($pastPoint > $user->getLastActivityDate()) {
            //TODO: implement logic last activity return false;
        }
        return true;
    }

    /**
     * Check if the current user has a verified swiss address
     *
     * @return bool
     */
    protected function hasVerifiedSwissAddress() {
        // Get shibboleth attributes from $_SERVER variable
        $homePostalAddress           = isset($_SERVER['homePostalAddress']) ? $_SERVER['homePostalAddress']: null;
        $swissLibraryPersonResidence = isset($_SERVER['swissLibraryPersonResidence']) ?
            $_SERVER['swissLibraryPersonResidence'] : null;

        if ($swissLibraryPersonResidence){
            if($swissLibraryPersonResidence === 'CH')
                return true;
        } else {
            if ($homePostalAddress &&
                $this->isAddressInSwitzerland($homePostalAddress) &&
                $this->isVerifiedHomePostalAddress()
            )
                return true;
        }
        return false;
    }

    protected function isVerifiedHomePostalAddress()
    {
        // TODO: Check if user has verified his home postal address
        return true;
    }

    /**
     * Check if the temporary access of the user is still valid.
     *
     * @param NationalLicenceUser $user
     * @return bool
     */
    protected function isTemporaryAccessCurrentlyValid($user) {
        if(new \DateTime() > $user->getExpirationDate()) {
            return false;
        }
        return true;
    }

    /**
     * Get a database table object.
     *
     * @param string $table Name of table to retrieve
     *
     * @return \VuFind\Db\Table\Gateway
     */
    protected function getTable($table)
    {
        return $this->getServiceLocator()
            ->get('VuFind\DbTablePluginManager')
            ->get($table);
    }

    /**
     * Check if it is a swiss address
     *
     * @param string $homeAddressString Home address from Swiss edu-ID account
     * @return bool                     True if it's a Swiss address False otherwise
     */
    public function isAddressInSwitzerland($homeAddressString)
    {
        $parts = explode("$", $homeAddressString);
        $state = $parts[count($parts)-1];
        return $state === 'Switzerland';
    }

    /**
     * Check if the user account is blocked
     * @param NationalLicenceUser $user
     * @throws Exception
     */
    protected function checkIfUserIsBlocked($user)
    {
        if($user->isBlocked()){
            throw new Exception("snl.impossibleProcessTheActionUserAccountBlockedError");
        }
    }
}