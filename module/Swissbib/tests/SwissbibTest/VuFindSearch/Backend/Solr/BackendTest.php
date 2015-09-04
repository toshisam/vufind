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
 * @package  SwissbibTest_VuFind_Backend_Solr
 * @author   Guenter Hipler  <guenter.hipler@unibas.ch>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://www.swissbib.org
 */
namespace SwissbibTest\VuFindSearch\Backend\Solr;

use PHPUnit_Framework_TestCase;

use VuFindSearch\Backend\Solr\Connector;
use VuFindSearch\Backend\Solr\HandlerMap;
use VuFindSearch\ParamBag;
use VuFindSearch\Query\Query;
use VuFindSearch\Backend\Solr\Response\Json\RecordCollection;

use Zend\Config\Config;
use Zend\Config\Reader\Ini;

use Swissbib\VuFindSearch\Backend\Solr\Backend;

/**
 * BackendTest
 *
 * @category Swissbib_VuFind2
 * @package  SwissbibTest_VuFind_Backend_Solr
 * @author   Guenter Hipler  <guenter.hipler@unibas.ch>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org
 */
class BackendTest extends PHPUnit_Framework_TestCase
{
    /**
     * Url
     *
     * @var string
     */
    protected $url;

    /**
     * UrlAdmin
     *
     * @var string
     */
    protected $urlAdmin;

    /**
     * Setup
     *
     * @return void
     */
    public function setUp()
    {
        $iniReader  = new Ini();
        $config     = new Config($iniReader->fromFile('../../../local/config/vufind/config.ini'));

        $this->url      = $config->get('Index')->get('url') . '/' . $config->get('Index')->get('default_core');
        $this->urlAdmin = $config->get('Index')->get('url') . '/admin';
    }

    /**
     * TestConnection
     *
     * @return void
     */
    public function testConnection()
    {
        $connector  = $this->getConnector('admin');
        $paramBag   = new ParamBag();
        $paramBag->set('action', ['status']);
        $paramBag->set('wt', ['json']);

        $response       = $connector->search($paramBag);
        $responseArray  = json_decode($response, true);

        $this->assertTrue(array_key_exists('sb-biblio', $responseArray['status']), 'Connection to Solr-Core sb-biblio failed.');
    }

    /**
     * TestResponseDataAmountMoreThanZero
     *
     * @return void
     */
    public function testResponseDataAmountMoreThanZero()
    {
        $backend    = new Backend($this->getConnector('select'));
        $result     = $backend->search(new Query(), 0, 100, $this->getParamBag());

        $this->assertTrue(0 < count($result->getRecords()), 'Number of found Records is more than zero.');
    }

    /**
     * TestResponseDataAmountBelowOrEqualToLimit
     *
     * @return void
     */
    public function testResponseDataAmountBelowOrEqualToLimit()
    {
        $backend    = new Backend($this->getConnector('select'));
        $limit      = 5;
        $result     = $backend->search(new Query(), 0, $limit, $this->getParamBag());

        $this->lessThanOrEqual(count($result->getRecords()), $limit, 'Number of found Records is less or equal to Limit.');
    }

    /**
     * TestResponseDataFormat
     *
     * @return void
     */
    public function testResponseDataFormat()
    {
        $backend    = new Backend($this->getConnector('select'));
        $result     = $backend->search(new Query(), 0, 100, $this->getParamBag());

        $this->assertTrue($result instanceof RecordCollection, 'Response is of Type Json\RecordCollection.');
    }

    /**
     * GetConnector
     *
     * @param string $name
     *
     * @return Connector
     */
    protected function getConnector($name = 'select')
    {
        if ($name === 'admin') {
            $url        = $this->urlAdmin;
            $handlerMap = new HandlerMap(['cores' => ['fallback' => true]]);
        } else {
            $url        = $this->url;
            $handlerMap = new HandlerMap(['select' => ['fallback' => true]]);
        }

        return new Connector($url, $handlerMap);
    }

    /**
     * GetParamBag
     *
     * @return ParamBag
     */
    protected function getParamBag()
    {
        $paramBag     = new ParamBag();

        $paramBag->set('q', ['a']);
        $paramBag->set('qf', ['title_short title_sub author series journals topic fulltext']);
        $paramBag->set('qt', ['edismax']);

        return $paramBag;
    }
}