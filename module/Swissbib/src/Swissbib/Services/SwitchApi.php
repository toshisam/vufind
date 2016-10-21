<?php
/**
 * Service used to manage the user registration process using the
 * National Licence registration platform by Switch.
 *
 * PHP version 5
 * Copyright (C) project swissbib, University Library Basel, Switzerland
 * http://www.swissbib.org  / http://www.swissbib.ch / http://www.ub.unibas.ch
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
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
use Zend\Http\Client;
use Zend\Http\Request;
use Zend\Http\Response;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Class SwitchApi.
 *
 * @category Swissbib_VuFind2
 * @package  Service
 * @author   Simone Cogno <scogno@snowflake.ch>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */
class SwitchApi implements ServiceLocatorAwareInterface
{
    /**
     * ServiceLocator.
     *
     * @var ServiceLocatorInterface
     */
    protected $serviceLocator;

    /**
     * Swissbib configuration.
     *
     * @var array
     */
    protected $config;

    /**
     * SwitchApi constructor.
     *
     * @param array $config Swissbib configuration.
     */
    public function __construct($config)
    {
        $this->config = $config['swissbib']['switch_api'];
    }

    /**
     * Set national-licence-compliant flag to the user.
     *
     * @param string $userExternalId External id
     *
     * @return void
     * @throws \Exception
     */
    public function setNationalCompliantFlag($userExternalId)
    {
        // 1 create a user
        $internalId = $this->createSwitchUser($userExternalId);
        // 2 Add user to the National Compliant group
        $this->addUserToNationalCompliantGroup($internalId);
        // 3 verify that the user is on the National Compliant group
        if (!$this->userIsOnNationalCompliantSwitchGroup($userExternalId)) {
            throw new \Exception(
                'Was not possible to add user to the ' .
                'national-licence-compliant group'
            );
        }
    }

    /**
     * Create a user in the National Licenses registration platform.
     *
     * @param string $externalId External id
     *
     * @return mixed
     * @throws \Exception
     */
    protected function createSwitchUser($externalId)
    {
        $client = $this->getBaseClient(Request::METHOD_POST, '/Users');
        $params = ['externalID' => $externalId];
        $client->setRawBody(json_encode($params, JSON_UNESCAPED_SLASHES));
        /**
         * Response.
         *
         * @var Response $response
         */
        $response = $client->send();
        $statusCode = $response->getStatusCode();
        $body = $response->getBody();
        if ($statusCode !== 200) {
            throw new \Exception("Status code: $statusCode result: $body");
        }
        $res = json_decode($body);

        return $res->id;
    }

    /**
     * Get an instance of the HTTP Client with some basic configuration.
     *
     * @param string $method   Method
     * @param string $relPath  Rel path
     * @param string $basePath Base path
     *
     * @return Client
     */
    protected function getBaseClient(
        $method = Request::METHOD_GET,
        $relPath = '', $basePath = null
    ) {
        if (empty($basePath)) {
            $basePath = $this->config['base_endpoint_url'];
        }
        $client = new Client(
            $basePath . $relPath, [
                'maxredirects' => 0,
                'timeout' => 30,
            ]
        );
        //echo $client->getUri();
        $client->setHeaders(
            [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ]
        );
        $client->setMethod($method);
        $username = $this->config['auth_user'];
        $passw = $this->config['auth_password'];
        if(empty($username) || empty($passw)) {
            if(empty(getenv('SWITCH_API_USER') || empty(getenv('SWITCH_API_PASSW')))) {
                throw new \Exception('Was not possible to find the SWITCH API credentials. '.
                    'Make sure you have correctly setup the environment variables '.
                    '"SWITCH_API_USER" and "SWITCH_API_PASSW" either in the'.
                    'apache setup or before launching the script.'
                );
            }
            $username = getenv('SWITCH_API_USER');
            $passw = getenv('SWITCH_API_PASSW');
        }
        $client->setAuth($username, $passw);

        return $client;
    }

    /**
     * Add user to the National Licenses Programme group on the National Licenses
     * registration platform.
     *
     * @param string $userInternalId User internal id
     *
     * @return void
     * @throws \Exception
     */
    protected function addUserToNationalCompliantGroup($userInternalId)
    {
        $client = $this->getBaseClient(
            Request::METHOD_PATCH, '/Groups/' .
            $this->config['national_licence_programme_group_id']
        );
        $params = [
            'schemas' => [
                $this->config['schema_patch'],
            ],
            'Operations' => [
                [
                    'op' => $this->config['operation_add'],
                    'path' => $this->config['path_member'],
                    'value' => [
                        [
                            '$ref' => $this->config['base_endpoint_url'] .
                                '/Users/' .
                                $userInternalId,
                            'value' => $userInternalId,
                        ],
                    ],
                ],
            ],
        ];
        $str = json_encode($params, JSON_PRETTY_PRINT);
        //echo "<pre> $str < /pre>";
        $rawData = json_encode($params, JSON_UNESCAPED_SLASHES);
        $client->setRawBody($rawData);
        $response = $client->send();
        $statusCode = $response->getStatusCode();
        $body = $response->getBody();
        if ($statusCode !== 200) {
            throw new \Exception("Status code: $statusCode result: $body");
        }
    }

    /**
     * Check if the user is on the National Licenses Programme group.
     *
     * @param string $userExternalId User external id
     *
     * @return bool
     * @throws \Exception
     */
    public function userIsOnNationalCompliantSwitchGroup($userExternalId)
    {
        $internalId = $this->createSwitchUser($userExternalId);
        $switchUser = $this->getSwitchUserInfo($internalId);
        foreach ($switchUser->groups as $group) {
            if ($group->value === $this->config['national_licence_programme_group_id']
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get user info from the National Licenses registration platform.
     *
     * @param string $internalId User external id
     *
     * @return mixed
     * @throws \Exception
     */
    protected function getSwitchUserInfo($internalId)
    {
        $client = $this->getBaseClient(Request::METHOD_GET, '/Users/' . $internalId);
        $response = $client->send();
        $statusCode = $response->getStatusCode();
        $body = $response->getBody();
        if ($statusCode !== 200) {
            throw new \Exception("Status code: $statusCode result: $body");
        }
        $res = json_decode($body);

        return $res;
    }

    /**
     * Unset the national compliant flag from the user.
     *
     * @param string $userExternalId User external id
     *
     * @return void
     * @throws \Exception
     */
    public function unsetNationalCompliantFlag($userExternalId)
    {
        // 1 create a user
        $internalId = $this->createSwitchUser($userExternalId);
        // 2 Add user to the National Compliant group
        $this->removeUserToNationalCompliantGroup($internalId);
        // 3 verify that the user is not in the National Compliant group
        if ($this->userIsOnNationalCompliantSwitchGroup($userExternalId)) {
            throw new \Exception(
                'Was not possible to remove the user to the ' .
                'national-licence-compliant group'
            );
        }
    }

    /**
     * Remove a national licence user from the national-licence-programme-group.
     *
     * @param string $userInternalId User internal id
     *
     * @return void
     * @throws \Exception
     */
    protected function removeUserToNationalCompliantGroup($userInternalId)
    {
        $client = $this->getBaseClient(
            Request::METHOD_PATCH,
            '/Groups/' . $this->config['national_licence_programme_group_id']
        );
        $params = [
            'schemas' => [
                $this->config['schema_patch'],
            ],
            'Operations' => [
                [
                    'op' => $this->config['operation_remove'],
                    'path' => $this->config['path_member'] .
                        "[value eq \"$userInternalId\"]",
                ],
            ],
        ];

        $rawData = json_encode($params, JSON_UNESCAPED_SLASHES);
        $client->setRawBody($rawData);
        $response = $client->send();
        $statusCode = $response->getStatusCode();
        $body = $response->getBody();
        if ($statusCode !== 200) {
            throw new \Exception("Status code: $statusCode result: $body");
        }
    }

    /**
     * Get updated fields about the national licence user.
     *
     * @param string $nameId       Name id
     * @param string $persistentId Persistent id
     *
     * @return NationalLicenceUser
     * @throws \Exception
     */
    public function getUserUpdatedInformation($nameId, $persistentId)
    {
        $updatedUser
            = (array)$this->getNationalLicenceUserCurrentInformation($nameId);
        $nationalLicenceFieldRelation = [
            'mobile' => 'mobile',
            'persistent_id' => 'persistent-id',
            'swiss_library_person_residence' => 'swissLibraryPersonResidence',
            'home_organization_type' => 'homeOrganizationType',
            'edu_id' => 'uniqueID',
            'home_postal_address' => 'homePostalAddress',
            'affiliation' => 'affiliation',
            'active_last_12_month' => 'swissEduIDUsage1y'
        ];
        $userFieldsRelation = [
            'username' => 'persistent-id',
            'firstname' => 'givenName',
            'lastname' => 'surname',
            'email' => 'mail',
        ];

        $nationalLicenceField = [];
        $userFields = [];
        foreach ($nationalLicenceFieldRelation as $key => $value) {
            if (array_key_exists($value, $updatedUser)) {
                $nationalLicenceField[$key] = $updatedUser[$value];
            }
        }
        foreach ($userFieldsRelation as $key => $value) {
            if (array_key_exists($value, $updatedUser)) {
                $userFields[$key] = $updatedUser[$value];
            }
        }
        /**
         * National Licence user.
         *
         * @var \Swissbib\VuFind\Db\Table\NationalLicenceUser $userTable
         */
        $userTable
            = $this->getTable('\\Swissbib\\VuFind\\Db\\Table\\NationalLicenceUser');

        /**
         * National licence user.
         *
         * @var NationalLicenceUser $user
         */
        return $userTable->updateRowByPersistentId(
            $persistentId,
            $nationalLicenceField,
            $userFields
        );
    }

    /**
     * Get the update attributes of a the national licence user.
     *
     *
     * @param string $nameId Name id
     *
     * @return NationalLicenceUser
     * @throws \Exception
     */
    protected function getNationalLicenceUserCurrentInformation($nameId)
    {
        //Make http request fro retrieve new edu-ID information usign the back-
        //channel api
        /**
         * Client.
         *
         * @var Client $client
         */
        $client = $this->getBaseClient(
            Request::METHOD_GET,
            $this->config['back_channel_endpoint_path'],
            $this->config['base_endpoint_url_back_channel']
        );
        $client->setParameterGet(
            [
                'entityID' => $this->config['back_channel_param_entityID'],
                'nameId' => $nameId,
            ]
        );
        $response = $client->send();
        $statusCode = $response->getStatusCode();
        $body = $response->getBody();
        if ($statusCode !== 200) {
            throw new \Exception("There were a problem retrieving data for user with name " .
                "id: $nameId. Status code: $statusCode result: $body");
        }

        return json_decode($body);
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
     * Get service locator.
     *
     * @return ServiceLocatorInterface
     */
    public function getServiceLocator()
    {
        return $this->serviceLocator;
    }

    /**
     * Set service locator.
     *
     * @param ServiceLocatorInterface $serviceLocator Service locator.
     *                                                 
     * @return void
     */
    public function setServiceLocator(ServiceLocatorInterface $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
    }
}
