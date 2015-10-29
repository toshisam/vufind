<?php
/**
 * RecordCollection
 *
 * PHP version 5
 *
 * Copyright (C) project swissbib, University Library Basel, Switzerland
 * http://www.swissbib.org  / http://www.swissbib.ch / http://www.ub.unibas.ch
 *
 * Date: 29/10/15
 * Time: 16:08
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
 * @package  VuFindSearch_Backend_Solr_Json
 * @author   Markus Mächler <markus.maechler@bithost.ch>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://www.swissbib.org
 */
namespace Swissbib\VuFindSearch\Backend\Solr\Response\Json;

use \VuFindSearch\Backend\Solr\Response\Json\RecordCollection
    as VuFindRecordCollection;

/**
 * RecordCollection
 *
 * @category Swissbib_VuFind2
 * @package  VuFindSearch_Backend_Solr_Json
 * @author   Markus Mächler <markus.maechler@bithost.ch>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org
 */
class RecordCollection extends VuFindRecordCollection
{
    /**
     * Return swissbib spellcheck information.
     *
     * @return Spellcheck
     */
    public function getSpellcheck()
    {
        if (!$this->spellcheck) {
            $this->spellcheck = new Spellcheck(
                $this->getRawSpellcheckSuggestions(), $this->getSpellcheckQuery()
            );
        }
        return $this->spellcheck;
    }
}