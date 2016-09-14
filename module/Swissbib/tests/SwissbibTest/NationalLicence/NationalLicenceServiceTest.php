<?php
/**
 * Created by PhpStorm.
 * User: nicolas
 * Date: 02.09.16
 * Time: 16:05
 */

namespace SwissbibTest\NationalLicence;

use Swissbib\Services\NationalLicence;
use VuFindTest\Unit\TestCase as VuFindTestCase;
use Zend\ServiceManager\ServiceManager;

class NationalLicenceServiceTest extends VuFindTestCase
{
    /**
     * This is just a try of a test of the national licence user service
     */
    public function testTest(){
        /** @var ServiceManager $sm */
        $sm = $this->getServiceManager();
        /** @var NationalLicence $nationalLicenceService */
        $nationalLicenceService = $sm->get('NationalLicenceService');
        $bool = $nationalLicenceService->isSwissPhoneNumber("+4179");
        $this->assertEqual(true, $bool);
    }
}