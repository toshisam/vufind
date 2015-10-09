<?php
 /**
 * InjectSwissbibSpellingListener
 *
 * PHP version 5
 *
 * Copyright (C) project swissbib, University Library Basel, Switzerland
 * http://www.swissbib.org  / http://www.swissbib.ch / http://www.ub.unibas.ch
 *
 * Date: 9/19/13
 * Time: 8:33 PM
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
 * @package  VuFind_Search_Solr
 * @author   Guenter Hipler  <guenter.hipler@unibas.ch>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://www.swissbib.org
 */
namespace Swissbib\VuFind\Search\Solr;

use VuFind\Search\Solr\InjectSpellingListener as VFSpellingListener;
use VuFindSearch\Backend\Solr\Response\Json\RecordCollection;
use Zend\EventManager\EventInterface;
use VuFindSearch\ParamBag;
use VuFindSearch\Backend\Solr\Response\Json\Spellcheck;
use VuFindSearch\Query\Query;

/**
 * InjectSwissbibSpellingListener
 *
 * @category Swissbib_VuFind2
 * @package  VuFind_Search_Solr
 * @author   Guenter Hipler <guenter.hipler@unibas.ch>
 * @author   Markus Mächler <markus.maechler@bithost.ch>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://www.vufind.org  Main Page
 */
class InjectSwissbibSpellingListener  extends VFSpellingListener
{
    /**
     * Set up spelling parameters.
     *
     * @param EventInterface $event Event
     *
     * @return EventInterface
     */
    public function onSearchPre(EventInterface $event)
    {
        if ($event->getParam('context') != 'search') {
            return $event;
        }
        $backend = $event->getTarget();
        if ($backend === $this->backend) {
            $params = $event->getParam('params');
            if ($params) {
                // Set spelling parameters when enabled:
                $sc = $params->get('spellcheck');
                if (isset($sc[0]) && $sc[0] != 'false') {
                    $this->active = true;
                    if (empty($this->dictionaries)) {
                        throw new \Exception(
                            'Spellcheck requested but no dictionary configured'
                        );
                    }

                    // Set relevant Solr parameters:
                    reset($this->dictionaries);
                    //deactivate initial spell checking, to do it only
                    //if there are no results
                    $params->set('spellcheck', 'false');
                    $params->set(
                        'spellcheck.dictionary', current($this->dictionaries)
                    );

                    // Turn on spellcheck.q generation in query builder:
                    $this->backend->getQueryBuilder()
                        ->setCreateSpellingQuery(true);
                }
            }
        }
        return $event;
    }

    /**
     * Inject additional spelling suggestions.
     *
     * @param EventInterface $event Event
     *
     * @return EventInterface
     */
    public function onSearchPost(EventInterface $event)
    {
        // Do nothing if spelling is disabled or context is wrong
        if (!$this->active || $event->getParam('context') != 'search') {
            return $event;
        }

        // Merge spelling details from extra dictionaries:
        $backend = $event->getParam('backend');
        if ($backend == $this->backend->getIdentifier()) {
            /** @var RecordCollection $result */
            $result = $event->getTarget();
            $params = $event->getParam('params');
            $spellcheckQuery = $params->get('spellcheck.q');
            reset($this->dictionaries);
            prev($this->dictionaries);
            if (!empty($spellcheckQuery)) {
                $this->aggregateSpellcheck(
                    $result->getSpellcheck(), end($spellcheckQuery)
                );
            }
        }
    }

    /**
     * Submit requests for more spelling suggestions.
     *
     * @param Spellcheck $spellcheck Aggregating spellcheck object
     * @param string     $query      Spellcheck query
     *
     * @return void
     */
    protected function aggregateSpellcheck(Spellcheck $spellcheck, $query)
    {
        foreach ($this->dictionaries as $dictionary) {
            $params = new ParamBag();

            $params->set('spellcheck', 'true');
            $params->set('spellcheck.dictionary', $dictionary);

            $queryObj = new Query($query, 'AllFields');
            $collection = $this->backend->search($queryObj, 0, 0, $params);

            $spellcheck->mergeWith($collection->getSpellcheck());
        }
    }
}