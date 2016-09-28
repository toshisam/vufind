<?php
/**
 * Created by PhpStorm.
 * User: nicolas
 * Date: 02.09.16
 * Time: 16:05
 */

namespace SwissbibTest\NationalLicence;

use Swissbib\Services\NationalLicence;
use Swissbib\VuFind\Db\Row\NationalLicenceUser;
use VuFindTest\Unit\TestCase as VuFindTestCase;
use Zend\ServiceManager\ServiceManager;
use SwissbibTest\Bootstrap;

class NationalLicenceServiceTest extends VuFindTestCase
{
    /**
     * @var ServiceManager $sm
     */
    protected $sm;

    /**
     * @var NationalLicence $nationalLicenceService
     */
    protected $nationalLicenceService;

    /**
     * Set up service manager and National Licence Service
     */
    public function setUp()
    {
        parent::setUp();
        $this->sm = Bootstrap::getServiceManager();
        $this->nationalLicenceService = $this->sm->get('Swissbib\NationalLicenceService');
    }

    /**
     * Test isSwissPhoneNumber method
     */
    public function testIsSwissPhoneNumber()
    {
        $testPhones = [
            "+41 793433434" => true,
            "+41 773433434" => true,
            "+41 763433434" => true,
            "+41 743433434" => false,
            "+39 793433434" => false,
            null            => false
        ];
        foreach ($testPhones as $phone => $expectedResult) {
            $res = $this->nationalLicenceService->isSwissPhoneNumber($phone);
            $this->assertEquals($expectedResult, $res);
        }
    }

    /**
     * Test isAddressInSwitzerland method
     */
    public function testAddressIsInSwitzerland()
    {
        $testAddresses = [
            'Route de l\'aurore 10$1700 Fribourg$Switzerland' => true,
            'Theobalds Road 29$WC2N London$England'           => false,
            'Roswiesenstrasse 100$8051 Zürich$Switzerland'    => true,
            null                                              => false
        ];
        foreach ($testAddresses as $testAddress => $expectedResult) {
            $res = $this->nationalLicenceService->isAddressInSwitzerland($testAddress);
            $this->assertEquals($expectedResult, $res);
        }

    }

    /**
     * Test isTemporaryAccessCurrentlyValid method
     */
    public function testIsTemporaryAccessCurrentlyValid()
    {
        /** @var NationalLicenceUser $user */
        $user = $this->getNationalLicenceUserObjectInstance();

        $user->setExpirationDate(new \DateTime());
        $res = $this->nationalLicenceService->isTemporaryAccessCurrentlyValid($user);
        $this->assertEquals(true, $res);

        $user->setExpirationDate((new \DateTime())->modify("-1 day"));
        $res = $this->nationalLicenceService->isTemporaryAccessCurrentlyValid($user);
        $this->assertEquals(false, $res);


        $user->setExpirationDate((new \DateTime())->modify("+1 day"));
        $res = $this->nationalLicenceService->isTemporaryAccessCurrentlyValid($user);
        $this->assertEquals(true, $res);
        fwrite(STDERR, print_r($res, TRUE));
    }

    public function test()
    {
        $user = $this->getNationalLicenceUserObjectInstance();
        $this->setFieldsToUser($user, [
            'condition_accepted' => false,
            'request_temporary_access' => false,
            'request_permanent_access' => false,
            'date_expiration' => null,
            'blocked' => false,
            'last_edu_id_activity' => null,
        ]);
        $res = $this->nationalLicenceService->hasAccessToNationalLicenceContent($user);
        $this->assertEquals(false, $res);

        $user = $this->getNationalLicenceUserObjectInstance();
        $this->setFieldsToUser($user, [
            'condition_accepted' => false,
            'request_temporary_access' => true,
            'request_permanent_access' => false,
            'date_expiration' => (new \DateTime())->modify("+14 days")->format('Y-m-d H:i:s'),
            'blocked' => false,
            'last_edu_id_activity' => (new \DateTime())->format('Y-m-d H:i:s'),
        ]);
        $res = $this->nationalLicenceService->hasAccessToNationalLicenceContent($user);
        $this->assertEquals(false, $res);

        $user = $this->getNationalLicenceUserObjectInstance();
        $this->setFieldsToUser($user, [
            'condition_accepted' => true,
            'request_temporary_access' => true,
            'request_permanent_access' => false,
            'date_expiration' => (new \DateTime())->modify("+14 days")->format('Y-m-d H:i:s'),
            'blocked' => false,
            'last_edu_id_activity' => (new \DateTime())->format('Y-m-d H:i:s'),
        ]);
        $res = $this->nationalLicenceService->hasAccessToNationalLicenceContent($user);
        $this->assertEquals(true, $res);


        $user = $this->getNationalLicenceUserObjectInstance();
        $this->setFieldsToUser($user, [
            'condition_accepted' => true,
            'request_temporary_access' => false,
            'request_permanent_access' => true,
            'date_expiration' => (new \DateTime())->modify("+14 days")->format('Y-m-d H:i:s'),
            'blocked' => false,
            'last_edu_id_activity' => (new \DateTime())->format('Y-m-d H:i:s'),
        ]);
        $res = $this->nationalLicenceService->hasAccessToNationalLicenceContent($user);
        $this->assertEquals(true, $res);
    }

    /**
     * Helper method to modify fields to a NationalLicenceUser instance.
     *
     * @param $user
     * @param $fields
     */
    protected function setFieldsToUser($user, $fields)
    {
        foreach ($fields as $key => $value) {
            $user->$key = $value;
        }
    }
    /**
     * Get an instance of the national licence user object.
     *
     * @return NationalLicenceUser
     */
    protected function getNationalLicenceUserObjectInstance()
    {
        /** @var \Swissbib\VuFind\Db\Table\NationalLicenceUser $userTable */
        $userTable = $this->sm
            ->get('VuFind\DbTablePluginManager')
            ->get("\\Swissbib\\VuFind\\Db\\Table\\NationalLicenceUser");
        /** @var NationalLicenceUser $user */
        $user = $userTable->createRow();

        return $user;
    }

    /**
     * Workaround to print in the unit test console.
     *
     * @param $variable
     */
    public function unitPrint($variable){
        fwrite(STDERR, print_r($variable, TRUE));
    }
}