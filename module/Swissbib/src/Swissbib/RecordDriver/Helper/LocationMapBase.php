<?php
/**
 * LocationMapBase
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
 * @package  RecordDriver_Helper
 * @author   Guenter Hipler  <guenter.hipler@unibas.ch>
 * @author   Oliver Schihin <oliver.schihin@unibas.ch>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://www.swissbib.org
 */
namespace Swissbib\RecordDriver\Helper;

use Swissbib\RecordDriver\Helper\Holdings as HoldingsHelper;

/**
 * Base class for location map
 * Contains all basic helper methods, to keep them away from the
 * custom implementations in LocationMap
 *
 * @category Swissbib_VuFind2
 * @package  Controller
 * @author   Guenter Hipler <guenter.hipler@unibas.ch>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */
abstract class LocationMapBase extends CustomizedMethods
{
    /**
     * Get a link for an item
     *
     * @param HoldingsHelper $holdingsHelper HoldingsHelper
     * @param Array          $item           Item
     *
     * @return String|Boolean
     */
    public function getLinkForItem(HoldingsHelper $holdingsHelper, array $item)
    {
        if ($this->isItemValidForLocationMap($item, $holdingsHelper)) {
            return $this->buildLocationMapLink($item, $holdingsHelper);
        }

        return false;
    }

    /**
     * Check whether location map link should be shown
     *
     * @param Array          $item           Item
     * @param HoldingsHelper $holdingsHelper HoldingsHelper
     *
     * @return Boolean
     */
    protected function isItemValidForLocationMap(array $item,
        HoldingsHelper $holdingsHelper
    ) {
        return $this->callMethod(
            'isItemValidForLocationMap',
            $item['institution'],
            [$item, $holdingsHelper]
        );
    }

    /**
     * Build link for location map
     *
     * @param Array          $item           Item
     * @param HoldingsHelper $holdingsHelper HoldingsHelper
     *
     * @return String|Boolean
     */
    protected function buildLocationMapLink(array $item,
        HoldingsHelper $holdingsHelper
    ) {
        return $this->callMethod(
            'buildLocationMapLink',
            $item['institution'],
            [$item, $holdingsHelper]
        );
    }

    /**
     * Build simple map link form link pattern and a value for PARAMS placeholder
     * Use this if you don't need a very special behaviour
     *
     * @param String $mapLinkPattern MapLinkPattern
     * @param String $paramsValue    ParamsValue
     *
     * @return String
     */
    protected function buildSimpleLocationMapLink($mapLinkPattern, $paramsValue)
    {
        $data = [
            'PARAMS' => urlencode($paramsValue)
        ];

        return $this->templateString($mapLinkPattern, $data);
    }
}
