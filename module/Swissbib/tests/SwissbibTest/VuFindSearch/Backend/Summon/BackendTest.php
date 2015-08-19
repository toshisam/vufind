<?php
/**
 * BackendTest
 *
 * PHP version 5
 *
 * Copyright (C) project swissbib, University Library Basel, Switzerland
 * http://www.swissbib.org  / http://www.swissbib.ch / http://www.ub.unibas.ch
 *
 * Date: 1/2/13
 * Time: 4:09 PM
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
 * @package  SwissbibTest_VuFind_Backend_Summon
 * @author   Guenter Hipler  <guenter.hipler@unibas.ch>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://www.swissbib.org
 */

namespace SwissbibTest\VuFindSearch\Backend\Summon;

use \PHPUnit_Framework_TestCase;

use \SerialsSolutions\Summon\Zend2 as Connector;
use \SerialsSolutions_Summon_Query as Query;

use \Zend\Config\Config;
use \Zend\Config\Reader\Ini;

use \VuFindSearch\Backend\Summon\Backend;

/**
 * BackendTest
 *
 * @category Swissbib_VuFind2
 * @package  SwissbibTest_VuFind_Backend_Summon
 * @author   Guenter Hipler  <guenter.hipler@unibas.ch>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org
 */
class BackendTest extends PHPUnit_Framework_TestCase
{
    /**
     * Connector
     *
     * @var Connector
     */
    protected $connector;

    /**
     * Setup
     *
     * @return void
     */
    public function setUp()
    {
        $iniReader = new Ini();
        $config = new Config($iniReader->fromFile('../../../local/config/vufind/config.ini'));

        $this->connector = new Connector($config->get('Summon')->get('apiId'), $config->get('Summon')->get('apiKey'));
    }

    /**
     * TestConnection
     *
     * @return void
     */
    public function testConnection()
    {
        try {
            $result = $this->connector->query(new Query('a'));
        } catch (Exception $e) {
            $this->fail("An error occured during the request.");
        }

        $this->assertTrue(!array_key_exists('errors', $result), "An error occured during the request.");
    }

    /**
     * TestDataAmountMoreThanZero
     *
     * @return void
     */
    public function testDataAmountMoreThanZero()
    {
        $result = $this->connector->query(new Query('a'));

        $this->assertTrue(count($result['documents']) > 0, "More than zero documents found.");
    }
} 