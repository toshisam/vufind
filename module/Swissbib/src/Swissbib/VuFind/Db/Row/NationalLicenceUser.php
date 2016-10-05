<?php
/**
 * Row Definition for national licence user.
 *
 * PHP version 5
 *
 * Copyright (C) Villanova University 2010.
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
 * @category VuFind
 *
 * @author   Simone Cogno <scogno@snowflake.ch>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 *
 * @link     https://vufind.org Main Site
 */
namespace Swissbib\VuFind\Db\Row;

use VuFind\Db\Row\RowGateway;
use VuFind\Db\Table\User;

class NationalLicenceUser extends RowGateway
{
    /** @var  \VuFind\Db\Row\User $relUser */
    protected $relUser;

    /**
     * Constructor.
     *
     * @param \Zend\Db\Adapter\Adapter $adapter Database adapter
     */
    public function __construct($adapter)
    {
        parent::__construct('id', 'national_licence_user', $adapter);
    }

    /**
     * Check is user has already requested a temporary access to the
     * National Licence content.
     *
     * @return mixed
     */
    public function hasAlreadyRequestedTemporaryAccess()
    {
        return $this->request_temporary_access;
    }

    /**
     * Get the expiration date of the temporary access to the
     * National Licence content.
     *
     * @return \DateTime
     */
    public function getExpirationDate()
    {
        return new \DateTime($this->date_expiration);
    }

    /**
     * Set the temporary access for 14 days.
     *
     * @return bool
     */
    public function setTemporaryAccess()
    {
        $this->request_temporary_access = true;
        $this->setExpirationDate((new \DateTime())->modify('+14 day'));
        $n = $this->save();
        if ($n > 0) {
            return true;
        }

        return false;
    }

    /**
     * Set expiration date.
     *
     * @param \DateTime $date
     */
    public function setExpirationDate($date)
    {
        $this->date_expiration = $date->format('Y-m-d H:i:s');
    }

    /**
     * Check if use has accepted terms and conditions.
     *
     * @return bool
     */
    public function hasAcceptedTermsAndConditions()
    {
        return $this->condition_accepted;
    }

    /**
     * Check if user account is blocked.
     *
     * @return bool
     */
    public function isBlocked()
    {
        return $this->blocked;
    }

    /**
     * Set condition accepted for the user.
     *
     * @param bool $accepted
     */
    public function setConditionsAccepted($accepted = false)
    {
        $this->condition_accepted = $accepted;
    }

    /**
     * Set persistent id field.
     *
     * @param string $persistentId
     */
    public function setPersistentId($persistentId)
    {
        $this->persistent_id = $persistentId;
    }

    /**
     * Set user id related to the User db table.
     *
     * @param int $id User id
     */
    public function setUserId($id)
    {
        $this->user_id = $id;
    }

    /**
     * Get the last activity date on the edu-ID account.
     *
     * @return \DateTime
     */
    public function getLastActivityDate()
    {
        return new \DateTime($this->last_edu_id_activity);
    }

    /**
     * Set the last activity date on the edu-ID account.
     *
     * @param \DateTime $date
     */
    public function setLastActivityDate(\DateTime $date)
    {
        $this->last_edu_id_activity = $date->format('Y-m-d H:i:s');
    }

    /**
     * Return the creation date of the national licence user.
     *
     * @return \DateTime
     */
    public function getCreatedDate()
    {
        return new \DateTime($this->created);
    }

    /**
     * @return bool
     */
    public function isIsInitialized()
    {
        return $this->isInitialized;
    }

    /**
     * @param bool $isInitialized
     */
    public function setIsInitialized($isInitialized)
    {
        $this->isInitialized = $isInitialized;
    }

    /**
     * Check if user has requested the permanent access.
     *
     * @return bool
     */
    public function hasRequestPermanentAccess()
    {
        return $this->request_permanent_access;
    }

    /**
     * Set the permanent access to the user.
     */
    public function setRequestPermanentAccess($bool = true)
    {
        $this->request_permanent_access = $bool;
    }

    /**
     * Get the unique id of the national licence user.
     *
     * @return string Edu-Id of the user
     */
    public function getEduId()
    {
        return $this->edu_id;
    }

    /**
     * @return \VuFind\Db\Row\User
     */
    public function getRelUser()
    {
        return $this->relUser;
    }

    /**
     * @param \VuFind\Db\Row\User $relUser
     */
    public function setRelUser($relUser)
    {
        $this->relUser = $relUser;
    }

    /**
     * Get the date of the last time that the extension request was made.
     *
     * @return \DateTime
     */
    public function getLastAccountExtensionRequest()
    {
        if (empty($this->last_account_extension_request)) {
            return;
        }

        return new \DateTime($this->last_account_extension_request);
    }

    /**
     * Set the last_account_extension_request field.
     *
     * @param $date
     */
    public function setLastAccountExtensionRequest($date)
    {
        $this->last_account_extension_request = $date->format('Y-m-d H:i:s');
    }

    public function unsetLastAccountextensionRequest()
    {
        $this->last_account_extension_request = null;
    }

    /**
     * Get nameID of the national licence user.
     *
     * @return string
     *
     * @throws \Exception
     */
    public function getNameId()
    {
        $parts = explode('!', $this->getPersistentId());
        if (count($parts) !== 3) {
            throw new \Exception('Invalid persistent id');
        }

        return $parts[2];
    }

    /**
     * Get persistent id.
     */
    public function getPersistentId()
    {
        return $this->persistent_id;
    }

    /**
     * Get the home_postal_address field.
     *
     * @return string
     */
    public function getHomePostalAddress()
    {
        return $this->home_postal_address;
    }

    /**
     * Get the swiss_library_person_residence field.
     *
     * @return string
     */
    public function getSwissLibraryPersonResidence()
    {
        return $this->swiss_library_person_residence;
    }
}
