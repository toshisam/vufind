<?php
/**
 * EmailServiceTest.
 *
 * PHP version 5
 * Copyright (C) project swissbib, University Library Basel, Switzerland
 * http://www.swissbib.org  / http://www.swissbib.ch / http://www.ub.unibas.ch
 * Date: 1/2/13
 * Time: 4:09 PM
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
 * @package  SwissbibTest_NationalLicence
 * @author   Simone Cogno  <scogno@snowflake.ch>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://www.swissbib.org
 */
namespace SwissbibTest\NationalLicence;

use Swissbib\Services\Email;
use VuFindTest\Unit\TestCase as VuFindTestCase;
use SwissbibTest\Bootstrap;
use Zend\ServiceManager\ServiceManager;

class EmailServiceTest extends VuFindTestCase
{
    /**
     * @var  Email $emailService
     */
    protected $emailService;
    /**
     * @var  ServiceManager $sm
     */
    protected $sm;

    /**
     * Set up service manager and National Licence Service.
     */
    public function setUp()
    {
        parent::setUp();
        $this->sm = Bootstrap::getServiceManager();
        $this->emailService = $this->sm->get('Swissbib\EmailService');
    }

    public function testSendAccountExtensionEmail()
    {
        try {
            $this->emailService->sendAccountExtensionEmail('scogno@snowflake.ch');
        } catch (\Exception $e) {
            self::fail($e->getMessage());
        }
    }

    /**
     * Workaround to print in the unit test console.
     *
     * @param $variable
     */
    public function unitPrint($variable)
    {
        fwrite(STDERR, print_r($variable, true));
    }
}
