<?php
/**
 * Hierarchy Driver Factory Class
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
 * @package  VuFind_Hierarchy_TreeDataSource
 * @author   Guenter Hipler <guenter.hipler@unibas.ch>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */
namespace Swissbib\VuFind\Hierarchy;

use Zend\ServiceManager\ServiceManager;
use Swissbib\VuFind\Hierarchy\TreeRenderer\JSTree as SwissbibJsTree;

/**
 * Hierarchy Data Source Factory Class
 * This is a factory class to build objects for managing hierarchies.
 *
 * @category Swissbib_VuFind2
 * @package  VuFind_Hierarchy_TreeDataSource
 * @author   Guenter Hipler <guenter.hipler@unibas.ch>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */
class Factory
{
    /**
     * GetHierarchyDriverSeries
     *
     * @param ServiceManager $sm ServiceManager
     *
     * @return mixed
     */
    public static function getHierarchyDriverSeries(ServiceManager $sm)
    {
        //Todo: Question GH:
        //Why this additional Factory method? Here we use another VuFind
        // Factory method which could be called directly
        // by the client in need for this type.
        return \VuFind\Hierarchy\Driver\Factory::get(
            $sm->getServiceLocator(), 'HierarchySeries'
        );
    }

    /**
     * GetJsTree
     *
     * @param ServiceManager $sm ServiceManager
     *
     * @return \Swissbib\VuFind\Hierarchy\TreeRenderer\JSTree
     */
    public static function getJSTree(ServiceManager $sm)
    {
        return new SwissbibJsTree(
            $sm->getServiceLocator()->get('ControllerPluginManager')->get('Url')
        );
    }
}
