<?php
/**
 * Solr spelling processor.
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

use VuFindSearch\Backend\Solr\Response\Json\Spellcheck;
use VuFindSearch\Query\AbstractQuery;
use VuFind\Search\Solr\SpellingProcessor as VuFindSpellingProcessor;
use VuFind\Search\Solr\Params;
use Zend\Config\Config;

/**
 * Extended version of the VuFind Solr Spelling Processor (based on
 * advanced Spellers like DirectIndexSpelling and .... )
 *
 * @category Swissbib_VuFind2
 * @package  VuFind_Search_Solr
 * @author   Guenter Hipler <guenter.hipler@unibas.ch>
 * @author   Markus Mächler <markus.maechler@bithost.ch>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://www.vufind.org  Main Page
 */
class SpellingProcessor extends VuFindSpellingProcessor
{
    /**
     * SpellingResults
     *
     * @var SpellingResults
     */
    protected $spellingResults;

    /**
     * Term limit for single terms if multiple words
     *
     * @var int
     */
    protected $termSpellingLimit = 1;

    /**
     * Term limit for single terms if only one word
     *
     * @var int
     */
    protected $termSingleSpellingLimit = 2;

    /**
     * CollationSpellingLimit
     *
     * @var int
     */
    protected $collationSpellingLimits = 2;

    /**
     * Constructor
     *
     * @param SpellingResults $spellingResults Spelling configuration (optional)
     */
    public function __construct(SpellingResults $spellingResults)
    {
        parent::__construct(null);

        $this->spellingResults = $spellingResults;
    }

    /**
     * Get raw spelling suggestions for a query.
     *
     * @param Spellcheck    $spellcheck Complete spellcheck information
     * @param AbstractQuery $query      Query for which info should be retrieved
     *
     * @return array
     * @throws \Exception
     */
    public function getSuggestions(Spellcheck $spellcheck, AbstractQuery $query)
    {
        if (!$this->spellingResults->hasSuggestions()) {
            $this->spellingResults->setSpellingQuery($query);
            $i = 1;
            $collationSpellingCount = 0;
            $termLimit = count($this->tokenize($query->getAllTerms())) === 1 ?
                $this->termSingleSpellingLimit : $this->termSpellingLimit;
            foreach ($spellcheck as $term => $info) {
                if (is_array($info) && isset($info[0]) && isset($info[0][0])
                    && $info[0][0] === "collationQuery"
                ) {
                    $this->spellingResults->addCollocationSOLRStructure($info);
                } elseif (++$i && $i <= $this->getSpellingLimit()
                    && array_key_exists("suggestion", $info)
                ) {
                    //no so called collation suggestions are based on the
                    // single term part of the spelling query
                    $numberTermSuggestions = 0;
                    foreach ($info['suggestion'] as $termSuggestion) {
                        $numberTermSuggestions++;
                        if ($numberTermSuggestions > $termLimit) {
                            break;
                        }
                        $this->spellingResults->addTerm(
                            $term, $termSuggestion['word'], $termSuggestion['freq']
                        );
                    }

                }

            }
        }

        return $this->spellingResults;
    }

    /**
     * Process spelling suggestions.
     *
     * @param array  $suggestions Raw suggestions from getSuggestions()
     * @param string $query       Spelling query
     * @param Params $params      Params helper object
     *
     * @return array
     */
    public function processSuggestions($suggestions, $query, Params $params)
    {
        return $suggestions;
    }
}