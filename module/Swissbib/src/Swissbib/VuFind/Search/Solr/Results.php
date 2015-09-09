<?php
/**
 * Results
 *
 * PHP version 5
 *
 * Copyright (C) project swissbib, 2015.
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
 * @package  VuFind_Search_Solr
 * @author   Guenter Hipler <guenter.hipler@unibas.ch>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://www.swissbib.org  Main Page
 */
namespace Swissbib\VuFind\Search\Solr;

use VuFind\Search\Solr\Results as VuFindSolrResults;

use VuFind\Search\Solr\SpellingProcessor;

/**
 * Class to extend the core VF2 SOLR functionality related to Solr Results
 *
 * @category Swissbib_VuFind2
 * @package  VuFind_Search_Solr
 * @author   Guenter Hipler <guenter.hipler@unibas.ch>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://www.vufind.org  Main Page
 */
class Results extends VuFindSolrResults
{
    /**
     * Target
     *
     * @var String
     */
    protected $target = 'swissbib';

    /**
     * SpellingResults
     *
     * @var SpellingResults
     */
    protected $sbSuggestions;

    /**
     * Get facet queries from result
     * Data is extracted
     * Format: {field, value, count, name}
     *
     * @param Boolean $onlyNonZero Only non zero values
     *
     * @return Array[]
     */
    protected function getResultQueryFacets($onlyNonZero = false)
    {
        /**
         * QueryFacets
         *
         * @var \ArrayObject $queryFacets
         */
        $queryFacets = $this->responseFacets->getQueryFacets();
        $facets        = [];

        foreach ($queryFacets as $facetName => $queryCount) {
            list($fieldName,$filterValue) = explode(':', $facetName, 2);

            if (!$onlyNonZero || $queryCount > 0) {
                $facets[] = [
                    'field'    => $fieldName,
                    'value'    => $filterValue,
                    'count'    => $queryCount,
                    'name'    => $facetName
                ];
            }
        }

        return $facets;
    }

    /**
     * Get facet list
     * Add institution query facets on top of the list
     *
     * @param Array|Null $filter Filter
     *
     * @return Array[]
     */
    public function getFacetList($filter = null)
    {


        /* start of VF2 implementation - has to be re-changed once multi domain
        translations even for factes are implemented*/

        // Make sure we have processed the search before proceeding:
        if (null === $this->responseFacets) {
            $this->performAndProcessSearch();
        }

        // If there is no filter, we'll use all facets as the filter:
        if (is_null($filter)) {
            $filter = $this->getParams()->getFacetConfig();
        }

        // Start building the facet list:
        $list = array();

        // Loop through every field returned by the result set
        $fieldFacets = $this->responseFacets->getFieldFacets();

        //how Facet-Configuration is used seems to be weired for me
        $configResultSettings = $this->getServiceLocator()->get('VuFind\Config')
            ->get($this->getOptions()->getFacetsIni())->Results_Settings;

        foreach (array_keys($filter) as $field) {
            $data = isset($fieldFacets[$field]) ? $fieldFacets[$field] : array();

            // Skip empty arrays:
            if (count($data) < 1) {
                continue;
            }
            // Initialize the settings for the current field
            $list[$field] = array();
            // Add the on-screen label
            $list[$field]['label'] = $filter[$field];
            // Build our array of values for this field
            $list[$field]['list']  = array();


            $translateInfo = $this->isFieldToTranslate($field);


            $list[$field]['displayLimit'] = isset(
                $configResultSettings
                    ->{'facet_limit_' . $translateInfo['normalizedFieldName']}
            ) ?
                $configResultSettings
                    ->{'facet_limit_' . $translateInfo['normalizedFieldName']} :
                $configResultSettings->facet_limit_default;
            // Loop through values:
            foreach ($data as $value => $count) {
                // Initialize the array of data about the current facet:
                $currentSettings = array();
                $currentSettings['value'] = $value;

                //if translation should be done (flag -translate) we have to
                // distinguis between
                //a) multi domain (field contains a colon). Then the signature of
                // the translation method differs (domain has to be indicated)
                //b) or simple translation

                $currentSettings['displayText']
                    = $translateInfo['translate'] ?
                    count($translateInfo['field_domain']) == 1 ?
                        $this->translate($value) :
                    $this->translate(
                        array($value , $translateInfo['field_domain'][1])
                    )  : $value;

                //$currentSettings['displayText']
                //    = $translate ?  $this->translate($value) : $value;


                $currentSettings['count'] = $count;
                $currentSettings['operator']
                    = $this->getParams()->getFacetOperator($field);
                $currentSettings['isApplied']
                    = $this->getParams()->hasFilter("$field:".$value)
                    || $this->getParams()->hasFilter("~$field:".$value);

                // Store the collected values:
                $list[$field]['list'][] = $currentSettings;
            }
        }

        return $list;

    }

    /**
     * Get special facets
     * - User favorite institutions
     *
     * @return Array[]
     */
    public function getMyLibrariesFacets()
    {
        $queryFacets    = $this->getResultQueryFacets(true);
        $list = [];

        $configQuerySettings = $this->getServiceLocator()->get('VuFind\Config')
            ->get($this->getOptions()->getFacetsIni())->QueryFacets;
        if (count($queryFacets) > 0 && isset($configQuerySettings)) {
            $configResultSettings = $this->getServiceLocator()->get('VuFind\Config')
                ->get($this->getOptions()->getFacetsIni())->Results_Settings;

            foreach ($queryFacets as $queryFacet) {

                if (isset($configQuerySettings[$queryFacet['field']])) {
                    $facetGroupName = $queryFacet['field'];

                    if (!isset($list[$facetGroupName])) {
                        $list[$facetGroupName] = [];
                    }
                    if (!isset($list[$facetGroupName]['label'])) {
                        $list[$facetGroupName]['label']
                            = $configQuerySettings[$queryFacet['field']];
                    }

                    $translateInfo = $this->isFieldToTranslate($queryFacet['field']);

                    if (!isset($list[$facetGroupName]['displayLimit'])) {
                        $list[$facetGroupName]['displayLimit'] = isset(
                            $configResultSettings->{'facet_limit_' .
                            $translateInfo['normalizedFieldName']}
                        ) ? $configResultSettings->{'facet_limit_' .
                                $translateInfo['normalizedFieldName']}
                          : $configResultSettings->facet_limit_default;
                    }

                    if (!isset($list[$facetGroupName]['field'])) {
                        $list[$facetGroupName]['field'] = $facetGroupName;
                    }

                    if (!isset($list[$facetGroupName]['list'])) {
                        $list[$facetGroupName]['list'] = [];
                    }

                    $currentSettings = [];

                    $currentSettings['displayText']
                        = $translateInfo['translate'] ?
                        count($translateInfo['field_domain']) == 1 ?
                            $this->translate($queryFacet['value']) :
                        $this->translate(
                            [
                                $queryFacet['value'] ,
                                $translateInfo['field_domain'][1]
                            ]
                        )  : $queryFacet['value'];

                    $currentSettings['isApplied'] = $this->getParams()
                        ->hasFilter($facetGroupName . ":" . $queryFacet['value'])
                            || $this->getParams()->hasFilter(
                                "~" . $facetGroupName . ":" . $queryFacet['value']
                            );

                    $currentSettings['count'] = $queryFacet['count'];
                    $currentSettings['value'] = $queryFacet['value'];

                    $list[$facetGroupName]['list'][] = $currentSettings;
                }
            }
        }

        return $list;
    }

    /**
     * GetTarget
     *
     * @return String $target
     */
    public function getTarget()
    {
        return $this->target;
    }

    /**
     * PerformSearch
     *
     * @throws \Exception
     * @throws \VuFindSearch\Backend\Exception\BackendException
     *
     * @return void
     */
    protected function performSearch()
    {
        $query  = $this->getParams()->getQuery();
        $limit  = $this->getParams()->getLimit();
        $offset = $this->getStartRecord() - 1;
        $params = $this->getParams()->getBackendParameters();
        $searchService = $this->getSearchService();

        try {
            $collection = $searchService
                ->search($this->backendId, $query, $offset, $limit, $params);
        } catch (\VuFindSearch\Backend\Exception\BackendException $e) {
            // If the query caused a parser error, see if we can clean it up:
            if ($e->hasTag('VuFind\Search\ParserError')
                && $newQuery = $this->fixBadQuery($query)
            ) {
                // We need to get a fresh set of $params, since the previous one was
                // manipulated by the previous search() call.
                $params = $this->getParams()->getBackendParameters();
                $collection = $searchService
                    ->search($this->backendId, $newQuery, $offset, $limit, $params);
            } else {
                throw $e;
            }
        }

        //code aus letztem VuFind Core
        $this->responseFacets = $collection->getFacets();
        $this->resultTotal = $collection->getTotal();

        if ($this->resultTotal == 0) {

            //we use spellchecking only in case of 0 hits

            $params = $this->getParams()->getSpellcheckBackendParameters();
            try {
                $recordCollectionSpellingQuery = $searchService
                    ->search($this->backendId, $query, $offset, $limit, $params);
            } catch (\VuFindSearch\Backend\Exception\BackendException $e) {
                //todo: some kind of logging?
                throw $e;

            }

            // Processing of spelling suggestions
            $spellcheck = $recordCollectionSpellingQuery->getSpellcheck();
            $this->spellingQuery = $spellcheck->getQuery();

            //GH: I introduced a special type for suggestions provided by the SOLR
            // index in opposition to the VF2 core implementation where a simple
            // array structure is used a specialized type makes it much easier to
            // use the suggestions in the view script
            //the object variable suggestions is already used by VF2 core
            $this->sbSuggestions = $this->getSpellingProcessor()
                ->getSuggestions($spellcheck, $this->getParams()->getQuery());
        }

        // Construct record drivers for all the items in the response:
        $this->results = $collection->getRecords();
    }

    /**
     * GetSpellingProcessor
     *
     * @return mixed
     */
    public function getSpellingProcessor()
    {
        if (null === $this->spellingProcessor) {
            $this->spellingProcessor = $this->getServiceLocator()
                ->get("sbSpellingProcessor");
        }

        return $this->spellingProcessor;
    }

    /**
     * Utility method to inspect multi domain translation for facets
     *
     * @param String $field Field
     *
     * @return array
     */
    protected function isFieldToTranslate($field)
    {
        $translateInfo = [];

        //getTranslatedFacets returns the entries in
        // Advanced_Settings -> translated_facets
        $refValuesToTranslate = $this->getOptions()->getTranslatedFacets();
        //is the current field a facet which should be translated?
        //we have to use this customized filter mechanism because facets going
        // to be translated are indicated in conjunction with their domain
        // facetName:domainName
        $fieldToTranslateInArray =  array_filter(
            $refValuesToTranslate, function ($passedValue) use ($field) {
                //return true, if the field shoul be translated
                //either $field==value in arra with facets to be translated
                // (simple translation)
                //or multi domain translation where the domain is part of the
                // configuration fieldname:domainName

                return $passedValue === $field
                    || count(
                        preg_grep("/" . $field . ":" . "/", [$passedValue])
                    ) > 0;
            }
        );

        //Did we detect the field should be translated
        // (field is part of the filtered array)
        $translateInfo['translate'] = count($fieldToTranslateInArray) > 0;

        //this name is always without any colons and could be used in
        // further processing

        $translateInfo['normalizedFieldName'] = $field;
        $translateInfo['field_domain'] = [];

        $fieldToTranslate = $translateInfo['translate'] ?
            current($fieldToTranslateInArray) : null;

        if ($translateInfo['translate']) {
            $translateInfo['field_domain']
                = strstr($fieldToTranslate, ':') === false ?
            [$field] :
            [$field,substr(
                $fieldToTranslate, strpos($fieldToTranslate, ':') + 1
            )];

            //normalizedFieldName contains only the fieldname without any colons as
            // seperator for the domain name (it's handy)
            $translateInfo['normalizedFieldName']
                = $translateInfo['field_domain'][0];
        }

        return $translateInfo;
    }

    /**
     * Turn the list of spelling suggestions into an array of urls
     *   for on-screen use to implement the suggestions.
     *
     * @return array Spelling suggestion data arrays
     */
    public function getSpellingSuggestions()
    {
        return $this->sbSuggestions;
    }
}
