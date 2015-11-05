<?php
/**
 * Extended version of the VuFind Solr Backend Factory
 *
 * PHP version 5
 *
 * Copyright (C) project swissbib, University Library Basel, Switzerland
 * http://www.swissbib.org  / http://www.swissbib.ch / http://www.ub.unibas.ch
 *
 * Date: 8/19/13
 * Time: 10:21 PM
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
 * @package  VuFind_Search_Factory
 * @author   Fabian Erni <ferni@snowflake.ch>
 * @author   Guenter Hipler  <guenter.hipler@unibas.ch>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://www.swissbib.org
 */
namespace Swissbib\VuFind\Search\Factory;

use Swissbib\VuFind\Search\Backend\Solr\LuceneSyntaxHelper;
use Swissbib\VuFind\Search\Solr\InjectSwissbibSpellingListener;
use VuFind\Search\Factory\SolrDefaultBackendFactory
    as VuFindSolrDefaultBackendFactory;
use VuFind\Search\Solr\FilterFieldConversionListener;
use VuFind\Search\Solr\MultiIndexListener;
use VuFindSearch\Backend\Solr\Backend;
use VuFind\Search\Solr\V3\ErrorListener as LegacyErrorListener;
use VuFind\Search\Solr\V4\ErrorListener;

use Swissbib\Highlight\SolrConfigurator as HighlightSolrConfigurator;
use VuFindSearch\Backend\Solr\Connector;
use VuFindSearch\Backend\Solr\Response\Json\RecordCollectionFactory;
use Swissbib\VuFindSearch\Backend\Solr\QueryBuilder;

/**
 * SolrDefaultBackendFactory
 *
 * @category Swissbib_VuFind2
 * @package  VuFind_Search_Factory
 * @author   Guenter Hipler <guenter.hipler@unibas.ch>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */
class SolrDefaultBackendFactory extends VuFindSolrDefaultBackendFactory
{
    /**
     * Creating Listeners
     *
     * @param Backend $backend Backend
     *
     * @return void
     */
    protected function createListeners(Backend $backend)
    {
        $events = $this->serviceLocator->get('SharedEventManager');

        // Load configurations:
        $config = $this->config->get('config');
        $search = $this->config->get($this->searchConfig);
        $facet = $this->config->get($this->facetConfig);

        // Highlighting
        $this->getInjectHighlightingListener($backend, $search)->attach($events);

        // Conditional Filters
        if (isset($search->ConditionalHiddenFilters)
            && $search->ConditionalHiddenFilters->count() > 0
        ) {
            $this->getInjectConditionalFilterListener($search)->attach($events);
        }

        // Spellcheck
        /*
        if (isset($config->Spelling->enabled) && $config->Spelling->enabled) {
            if (isset($config->Spelling->simple) && $config->Spelling->simple) {
                $dictionaries = ['basicSpell'];
            } else {
                $dictionaries = ['default', 'basicSpell'];
            }
            $spellingListener = new InjectSpellingListener($backend, $dictionaries);
            $spellingListener->attach($events);
        }
        */

        // Apply field stripping if applicable:
        if (isset($search->StripFields) && isset($search->IndexShards)) {
            $strip = $search->StripFields->toArray();
            foreach ($strip as $k => $v) {
                $strip[$k] = array_map('trim', explode(',', $v));
            }
            $mindexListener = new MultiIndexListener(
                $backend,
                $search->IndexShards->toArray(),
                $strip,
                $this->loadSpecs()
            );
            $mindexListener->attach($events);
        }

        // Apply deduplication if applicable:
        if (isset($search->Records->deduplication)) {
            $this->getDeduplicationListener(
                $backend, $search->Records->deduplication
            )->attach($events);
        }

        // Attach hierarchical facet listener:
        $this->getHierarchicalFacetListener($backend)->attach($events);

        // Apply legacy filter conversion if necessary:
        $facets = $this->config->get($this->facetConfig);
        if (!empty($facets->LegacyFields)) {
            $filterFieldConversionListener = new FilterFieldConversionListener(
                $facets->LegacyFields->toArray()
            );
            $filterFieldConversionListener->attach($events);
        }

        // Attach hide facet value listener:
        if ($hfvListener = $this->getHideFacetValueListener($backend, $facet)) {
            $hfvListener->attach($events);
        }

        // Attach error listeners for Solr 3.x and Solr 4.x (for backward
        // compatibility with VuFind 1.x instances).
        $legacyErrorListener = new LegacyErrorListener($backend);
        $legacyErrorListener->attach($events);
        $errorListener = new ErrorListener($backend);
        $errorListener->attach($events);


        //Swissbib custom

        $spellingListener = new InjectSwissbibSpellingListener(
            $backend, ['default'], $config
        );
        $spellingListener->attach($events);

        $this->attachHighlightSolrConfigurator($backend);
    }

    /**
     * AttachHighlightSolrConfigurator
     *
     * @param Backend $backend Backend
     *
     * @return void
     */
    protected function attachHighlightSolrConfigurator(Backend $backend)
    {
        /**
         * HighlightSolrConfigurator
         *
         * @var HighlightSolrConfigurator $highlightListener
         */
        $highlightListener = $this->serviceLocator->get(
            'Swissbib\Highlight\SolrConfigurator'
        );

        $highlightListener->attach($backend);
    }

    /**
     * Create the SOLR backend.
     *
     * @param Connector $connector Connector
     *
     * @return Backend
     */
    protected function createBackend(Connector $connector)
    {
        //we can't use zje original funtion because Backend is overwritten
        // by Swissbib

        $backend = new Backend($connector);
        $backend->setQueryBuilder($this->createQueryBuilder());

        if ($this->logger) {
            $backend->setLogger($this->logger);
        }

        $manager = $this->serviceLocator->get('VuFind\RecordDriverPluginManager');
        $factory = new RecordCollectionFactory(
            [$manager, 'getSolrRecord'],
            'Swissbib\VuFindSearch\Backend\Solr\Response\Json\RecordCollection'
        );
        $backend->setRecordCollectionFactory($factory);

        return $backend;
    }

    /**
     * Create the query builder.
     *
     * @return QueryBuilder
     */
    protected function createQueryBuilder()
    {
        $specs   = $this->loadSpecs();
        $config = $this->config->get('config');
        $defaultDismax = isset($config->Index->default_dismax_handler)
            ? $config->Index->default_dismax_handler : 'dismax';
        $builder = new QueryBuilder($specs, $defaultDismax);

        // Configure builder:
        $search = $this->config->get($this->searchConfig);
        $caseSensitiveBooleans
            = isset($search->General->case_sensitive_bools)
            ? $search->General->case_sensitive_bools : true;
        $caseSensitiveRanges
            = isset($search->General->case_sensitive_ranges)
            ? $search->General->case_sensitive_ranges : true;
        $helper = new LuceneSyntaxHelper(
            $caseSensitiveBooleans, $caseSensitiveRanges
        );
        $builder->setLuceneHelper($helper);

        return $builder;
    }
}
