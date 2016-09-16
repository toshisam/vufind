<?php
/**
 * Service for manage the National Licence users.
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
 * @package  Services
 * @author   Simone Cogno <scogno@snowflake.ch>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
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
        $user = $this->getCurrentNationalLicenceUser($persistentId);

        $this->checkIfUserIsBlocked($user);

        if($user->hasAlreadyRequestedTemporaryAccess()) {
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
        $user->save();
    }

    /**
     * Get the current national licence user if it exists
     *
     * @param null $persistentId
     * @return NationalLicenceUser
     * @throws \Exception
     */
    public function getCurrentNationalLicenceUser($persistentId = null)
    {
        // If no id is passed in the parameter take de current user
        if(empty($persistentId) AND isset($_SERVER["persistent-id"])) {
            $persistentId = $_SERVER["persistent-id"];
        }
        if(empty($persistentId)) {
            throw new \Exception("Error retrieving the current user.");
        }
        /** @var \Swissbib\VuFind\Db\Table\NationalLicenceUser $userTable */
        $userTable = $this->getTable("\\Swissbib\\VuFind\\Db\\Table\\NationalLicenceUser");
        $user= $userTable->getUserByPersistentId($persistentId);
        if(empty($user)) {
            throw new \Exception("Impossible to retrieve the current user in the database");
        }
        return $user;
    }

    /**
     * Get a NationalLicenceUser or creates a new one if is not existing in the database
     *
     * @param string $persistentId Edu-id persistent id
     * @param array $userFields Array of national licence user fields with their values.
     * @return NationalLicenceUser $user
     */
    public function getOrCreateNationalLicenceUserIfNotExists($persistentId, $userFields = array())
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

    /**
     * Check if the current user is compliant with the Swiss National Licence
     *
     * @return bool
     */
    public function isNationalLicenceCompliant()
    {
        $user = $this->getCurrentNationalLicenceUser();

        // Has accepted terms and conditions
        /** @var NationalLicenceUser $user */
        if (!$user->hasAcceptedTermsAndConditions()) return false;
        // Is not blocked by the administrators
        if ($user->isBlocked()) return false;
        // Last activity at least in the last 12 months
        if(!$this->hasBeenActiveInLast12Months($user)) return false;
        // Has requested a temporary access || Has a verified home postal address
        $hasTemporaryAccess = $user->hasAlreadyRequestedTemporaryAccess() &&
                              $this->isTemporaryAccessCurrentlyValid($user);
        $hasVerifiedSwissAddress = $this->hasVerifiedSwissAddress();

        return $hasTemporaryAccess || $hasVerifiedSwissAddress ;
    }

    /**
     * Check if the user has been active in the last 12 Months
     *
     * @param NationalLicenceUser $user
     * @return bool
     */
    protected function hasBeenActiveInLast12Months($user) {
        //TODO: Change this method. This will be provided as a shibbboleth attribute by SWITCH.
        $pastPoint = (new \DateTime())->modify("-12 months");
        if ($pastPoint > $user->getLastActivityDate()) {
            return false;
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

    /**
     * Checks if the user have a verified home postal address in their edu-ID account
     *
     * @return bool
     */
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
     *
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