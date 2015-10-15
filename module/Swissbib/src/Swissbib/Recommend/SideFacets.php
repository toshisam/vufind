<?php
/**
 * SideFacets Recommendations Module
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
 * @category VuFind2
 * @package  Recommendations
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:recommendation_modules Wiki
 */
namespace Swissbib\Recommend;

use VuFind\Recommend\SideFacets as VFSideFacets;

/**
 * SideFacets Recommendations Module
 *
 * This class provides recommendations displaying facets beside search results
 *
 * @category VuFind2
 * @package  Recommendations
 * @author   Guenter Hipler <guenter.hipler@unibas.ch>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:recommendation_modules Wiki
 */
class SideFacets extends VFSideFacets
{
    /**
     * Result_Settings
     *
     * @var array
     */
    protected $resultSettings = [];

    /**
     * Returns libraries
     *
     * @return mixed
     */
    public function getMyLibraries()
    {
        return $this->results->getMyLibrariesFacets();
    }

    /**
     * Store the configuration of the recommendation module.
     *
     * @param string $settings Settings from searches.ini.
     *
     * @return void
     */
    public function setConfig($settings)
    {
        parent::setConfig($settings);

        $settings = explode(':', $settings);
        $iniName = isset($settings[2]) ? $settings[2] : 'facets';

        // Load the desired facet information...
        $config = $this->configLoader->get($iniName);
        if (isset($config->Results_Settings)) {
            $this->resultSettings = $config->Results_Settings->toArray();
        }

    }

    /**
     * Returns limits for facets in simple array structure for easy use.
     *
     * @return array
     */
    public function getFacetLimits()
    {

        $facetLimits = [];

        foreach ($this->resultSettings as $k => $v) {
            if (substr($k, 0, 12) === 'facet_limit_') {
                $facetName = explode('_', $k)[2];
                $facetLimits [$facetName] = $v;
            }
        }

        return $facetLimits;

    }

    /**
     * Store the configuration of the recommendation module.
     *
     * @param string $facetField name of the facetField we are looking for the
     *                           limit which should be displayed.
     *
     * @return integer
     */
    public function getFacetLimit($facetField)
    {

        if (isset($this->resultSettings['facet_limit_' . $facetField])) {
            $limit = $this->resultSettings['facet_limit_' . $facetField];
        } else {
            $limit = isset($this->resultSettings['facet_limit_default']) ?
                $this->resultSettings['facet_limit_default'] : 100;
        }

        return $limit;

    }

}
