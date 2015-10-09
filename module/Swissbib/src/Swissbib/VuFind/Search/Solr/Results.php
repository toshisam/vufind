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
 * @author   Markus Mächler <markus.maechler@bithost.ch>
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
                $facets[$fieldName][$filterValue] = [
                    'label'    => $fieldName,
                    'value'    => $filterValue,
                    'count'    => $queryCount,
                    'name'    => $facetName
                ];
            }
        }

        return $facets;
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
            ->get($this->getOptions()->getFacetsIni())->QueryFacets->toArray();

        if (count($queryFacets) > 0 && isset($configQuerySettings)) {

            $translatedFacets = $this->getOptions()->getTranslatedFacets();

            foreach (array_keys($configQuerySettings) as $field) {
                $data = isset($queryFacets[$field]) ? $queryFacets[$field] : [];
                // Skip empty arrays:
                if (count($data) < 1) {
                    continue;
                }
                // Initialize the settings for the current field
                $list[$field] = [];
                // Add the on-screen label
                $list[$field]['label'] = $configQuerySettings[$field];
                // Build our array of values for this field
                $list[$field]['list']  = [];
                // Should we translate values for the current facet?
                if ($translate = in_array($field, $translatedFacets)) {
                    $translateTextDomain = $this->getOptions()
                        ->getTextDomainForTranslatedFacet($field);
                }
                // Loop through values:
                foreach ($data as $value => $count) {
                    // Initialize the array of data about the current facet:
                    $currentSettings = [];
                    $currentSettings['value'] = $value;
                    $currentSettings['displayText']
                        = $translate
                        ? $this->translate("$translateTextDomain::$value") : $value;
                    $currentSettings['count'] = $count['count'];
                    $currentSettings['operator']
                        = $this->getParams()->getFacetOperator($field);
                    $currentSettings['isApplied']
                        = $this->getParams()->hasFilter("$field:" . $value)
                        || $this->getParams()->hasFilter("~$field:" . $value);

                    // Store the collected values:
                    $list[$field]['list'][] = $currentSettings;
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
}
