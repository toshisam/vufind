<?php
/**
 * Created by PhpStorm.
 * User: swissbib
 * Date: 2/20/15
 * Time: 11:05 AM
 */

namespace Swissbib\VuFindSearch\Backend\Solr\Response\Json;

use VuFindSearch\Backend\Solr\Response\Json\Spellcheck as VFSearchSpellcheck;



class Spellcheck extends VFSearchSpellcheck{

    public function __construct(array $spellcheck, $query)
    {
        parent::__construct($spellcheck, $query);
        }


}