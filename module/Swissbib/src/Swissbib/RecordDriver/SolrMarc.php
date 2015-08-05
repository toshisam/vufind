<?php

/**
 * swissbib / VuFind swissbib enhancements for MARC records in Solr
 *
 * PHP version 5
 *
 * Copyright (C) project swissbib, University Library Basel, Switzerland
 * http://www.swissbib.org  / http://www.swissbib.ch / http://www.ub.unibas.ch
 *
 * Date: 1/2/13
 * Time: 4:09 PM
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
 * @category swissbib_VuFind2
 * @package  RecordDriver
 * @author   Guenter Hipler  <guenter.hipler@unibas.ch>
 * @author   Oliver Schihin <oliver.schihin@unibas.ch>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:record_drivers Wiki
 * @link     http://www.swissbib.org Project Wiki
 */

namespace Swissbib\RecordDriver;

use Zend\Filter\Null;
use Zend\I18n\Translator\TranslatorInterface as Translator;
use Zend\ServiceManager\ServiceLocatorInterface;
use VuFind\RecordDriver\SolrMarc as VuFindSolrMarc;
use Swissbib\RecordDriver\Helper\Holdings as HoldingsHelper;

class SolrMarc extends VuFindSolrMarc implements SwissbibRecordDriver
{

    /**
     * @var    HoldingsHelper
     */
    protected $holdingsHelper;

    /**
     * @var    Boolean        Change behaviour if getFormats() to return openUrl compatible formats
     */
    protected $useOpenUrlFormats = false;

    /**
     * @var    Boolean
     */
    protected $useMostSpecificFormat = false;

    /**
     * @var    Array    Used also for field 100        _ means repeatable
     */
    protected $personFieldMap = [
        'a' => 'name',
        'b' => 'numeration',
        '_c' => 'titles', // R
        'd' => 'dates',
        '_e' => 'relator', // R
        'f' => 'date_of_work',
        'g' => 'misc',
        'l' => 'language',
        '_n' => 'number_of_parts', // R
        'q' => 'fullername',
        'D' => 'forname',
        't' => 'title_of_work',
        '_8' => 'extras',
        '9' => 'unknownNumber'
    ];

    /*
     * @var Array used for field 110/710
     *  _ is repeatable
     */
    protected $corporationFieldMap = [
        'a' => 'name',
        '_b' => 'unit',
        'c' => 'meeting_location',
        '_d' => 'meeting_date',
        '_e' => 'relator',
        'f' => 'date',
        'g' => 'misc',
        'h' => 'medium',
        'i' => 'relationship',
        '_k' => 'form_subheading',
        'l' => 'language',
        '_m' => 'music_performance_medium',
        '_n' => 'parts_number',
        '_p' => 'parts_name',
        'r' => 'music_key',
        's' => 'version',
        't' => 'title',
        'u' => 'affiliation',
        'x' => 'issn',
        '3' => 'materials_specified',
        '4' => 'relator_code',
        '5' => 'institution',
        '_8' => 'label'
    ];

    protected $protocolWrapper = null;

    protected $multiValuedFRBRField = true;

    /**
     * @var    Array    List of all Elements of the description, to figure out whether to show tab or not
     */
    protected $partsOfDescription = array(
        'ISBNs', 'ISSNs', 'ISMNs', 'DOIs', 'URNs', 'AllSubjectVocabularies',
        'Series', 'AltTitle', 'NewerTitles', 'PreviousTitles',
        'GeneralNotes', 'DissertationNotes', 'BibliographyNotes', 'AccessRestrictions',
        'ProductionCredits', 'OriginalTitle', 'PerformerNote', 'Awards', 'CitationNotes',
        'OriginalVersionNotes', 'CopyNotes', 'SystemDetails', 'RelationshipNotes'
    );


    public function __construct($mainConfig = null, $recordConfig = null,
                                $searchSettings = null, $protocolWrapper
    ) {
        parent::__construct($mainConfig, $recordConfig, $searchSettings);


        $this->multiValuedFRBRField = isset($searchSettings->General->multiValuedFRBRField) ? $searchSettings->General->multiValuedFRBRField : true;
        $this->protocolWrapper = $protocolWrapper;
    }


    /**
     * Wrapper for getOpenURL()
     * Set flag to get special values from getFormats()
     *
     * @see        getFormats()
     * @return    String
     */
    public function getOpenURL()
    {
        // get the coinsID from config.ini or default to swissbib.ch
        $coinsID = $this->mainConfig->OpenURL->rfr_id;
        if (empty($coinsID)) {
            $coinsID = 'swissbib.ch';
        }

        // Get a representative publication date, using the view helper:
        $pubDate = $this->getHumanReadablePublicationDates();

        // Start an array of OpenURL parameters:
        $params = array(
            'ctx_ver' => 'Z39.88-2004',
            'ctx_enc' => 'info:ofi/enc:UTF-8',
            'rfr_id' => "info:sid/{$coinsID}:generator",
            'rft.title' => $this->getTitle(),
            'rft.date' => isset($pubDate[0]) ? $pubDate[0] : null,
        );

        $this->useOpenUrlFormats = true;
        $format = $this->getOpenURLFormat();
        switch ($format) {
            case 'Book':
                $params['rft_val_fmt'] = 'info:ofi/fmt:kev:mtx:book';
                $params['rft.genre'] = 'book';
                $params['rft.btitle'] = $params['rft.title'];
                $series = $this->getSeries();
                if (count($series) > 0) {
                    // Handle both possible return formats of getSeries:
                    $params['rft.series'] = is_array($series[0]) ?
                        $series[0]['name'] : $series[0];
                }
                $authors[] = $this->getPrimaryAuthor();
                $addauthors = $this->getSecondaryAuthors();
                if (!empty($addauthors)) {
                    foreach ($addauthors as $addauthor) {
                        $authors[] = $addauthor;
                    }
                }
                if (!empty($authors)) {
                    foreach ($authors as $author) {
                        $params['rft.au'][] = $author;
                    }
                }
                $corporations = $this->getCorporationNames(true);
                if (!empty($corporations)) {
                    foreach ($corporations as $corporation) {
                        $params['rft.aucorp '][] = $corporation;
                    }
                }

                $publications = $this->getPublicationDetails();
                foreach ($publications as $field) {
                    $pubPlace = $field->getPlace();
                    $pubName = $field->getName();

                    if (!empty($pubPlace)) {
                        $params['rft.place'] = preg_replace('/ : .*$/', '', $pubPlace);
                    }
                    if (!empty($pubName)) {
                        $params['rft.pub'] = preg_replace('/^.* : /', '', $pubName);
                    }
                }


                $params['rft.edition'] = $this->getEdition();
                $params['rft.isbn'] = $this->getCleanISBN();
                break;
            case 'Article':
                $params['rft_val_fmt'] = 'info:ofi/fmt:kev:mtx:journal';
                $params['rft.genre'] = 'article';
                $params['rft.issn'] = $this->getCleanISSN();
                // an article may have also an ISBN:
                $params['rft.isbn'] = $this->getCleanISBN();
                $params['rft.volume'] = $this->getContainerVolume();
                $params['rft.issue'] = $this->getContainerIssue();
                $params['rft.spage'] = $this->getContainerStartPage();
                // unset default title -- we only want jtitle/atitle here:
                unset($params['rft.title']);
                $params['rft.jtitle'] = $this->getContainerTitle();
                $params['rft.atitle'] = $this->getTitle();

                $authors[] = $this->getPrimaryAuthor();
                $addauthors = $this->getSecondaryAuthors();
                if (!empty($addauthors)) {
                    foreach ($addauthors as $addauthor) {
                        $authors[] = $addauthor;
                    }
                }
                if (!empty($authors)) {
                    foreach ($authors as $author) {
                        $params['rft.au'][] = $author;
                    }
                }
                $params['rft.format'] = $format;
                $langs = $this->getLanguages();
                if (count($langs) > 0) {
                    $params['rft.language'] = $langs[0];
                }
                break;
            case 'Journal':
                /* This is probably the most technically correct way to represent
                * a journal run as an OpenURL; however, it doesn't work well with
                * Zotero, so it is currently commented out -- instead, we just add
                * some extra fields and then drop through to the default case.
                $params['rft_val_fmt'] = 'info:ofi/fmt:kev:mtx:journal';
                $params['rft.genre'] = 'journal';
                $params['rft.jtitle'] = $params['rft.title'];
                $params['rft.issn'] = $this->getCleanISSN();
                $params['rft.au'] = $this->getPrimaryAuthor();
                break;
                */
                $params['rft_val_fmt'] = 'info:ofi/fmt:kev:mtx:journal';
                $params['rft.genre'] = 'journal';
                $params['rft.issn'] = $this->getCleanISSN();
                // Including a date in a title-level Journal OpenURL may be too
                // limiting -- in some link resolvers, it may cause the exclusion
                // of databases if they do not cover the exact date provided!
                //unset($params['rft.date']);
                // If we're working with the SFX resolver, we should add a
                // special parameter to ensure that electronic holdings links
                // are shown even though no specific date or issue is specified:
                //if (isset($this->mainConfig->OpenURL->resolver)
                // && strtolower($this->mainConfig->OpenURL->resolver) == 'sfx'
                //) {
                // $params['sfx.ignore_date_threshold'] = 1;
                //};                }
            default:
                $params['rft_val_fmt'] = 'info:ofi/fmt:kev:mtx:dc';

                $authors[] = $this->getPrimaryAuthor();
                $addauthors = $this->getSecondaryAuthors();
                if (!empty($addauthors)) {
                    foreach ($addauthors as $addauthor) {
                        $authors[] = $addauthor;
                    }
                }
                if (!empty($authors)) {
                    foreach ($authors as $author) {
                        $params['rft.au'][] = $author;
                    }
                }

                $publications = $this->getPublicationDetails();
                foreach ($publications as $field) {
                    $pubPlace = $field->getPlace();
                    $pubName = $field->getName();

                    if (!empty($pubPlace)) {
                        $params['rft.place'] = preg_replace('/ : .*$/', '', $pubPlace);
                    }
                    if (!empty($pubName)) {
                        $params['rft.pub'] = preg_replace('/^.* : /', '', $pubName);
                    }
                }

                $params['rft.format'] = $format;
                $langs = $this->getLanguages();
                if (count($langs) > 0) {
                    $params['rft.language'] = $langs[0];
                }
                break;
        }

        // Assemble the URL:
        $parts = array();
        foreach ($params as $key => $value) {
            if (is_array($value))
            {
                foreach ($value as $content) {
                    $parts[] = $key . '=' . urlencode($content);
                }
            }
            else {
                $parts[] = $key . '=' . urlencode($value);
            }
        }
        return implode('&', $parts);
    }


    /**
     * Get formats. By default, get translated values
     * If flag useOpenUrlFormats in class is set, get prepared formats for openUrl
     *
     * @return    String[]
     */
    public function getFormats()
    {
        if ($this->useOpenUrlFormats) {
            return $this->getFormatsOpenUrl();
        }  else if ($this->useMostSpecificFormat) {
            return $this->getMostSpecificFormat();
        } else {
            return $this->getFormatsTranslated();
        }
    }


    /**
     * Return an array of associative URL arrays with one or more of the following
     * keys:
     *
     * <li>
     *   <ul>desc: URL description text to display (optional)</ul>
     *   <ul>url: fully-formed URL (required if 'route' is absent)</ul>
     *   <ul>route: VuFind route to build URL with (required if 'url' is absent)</ul>
     *   <ul>routeParams: Parameters for route (optional)</ul>
     *   <ul>queryString: Query params to append after building route (optional)</ul>
     * </li>
     *
     * @return array
     */
    public function getURLs()
    {
        $retVal = array();

        // Which fields/subfields should we check for URLs?
        $fieldsToCheck = array(
            '856' => array('u', '3', 'z'), // Standard URL
            '956' => array('u', 'y'), // Standard URL
            //'555' => array('a')         // Cumulative index/finding aids
        );

        foreach ($fieldsToCheck as $field => $subfields) {
            $urls = $this->marcRecord->getFields($field);
            if ($urls) {
                foreach ($urls as $url) {
                    // Is there an address in the current field?
                    $address = $url->getSubfield('u');
                    if ($address) {
                        $address = $address->getData();

                        $tSubField = end($subfields);

                        $descSubField = $url->getSubfield($tSubField);

                        // if no content in subfield z/y, try subfield 3
                        if (!$descSubField) {
                            $descSubField = $url->getSubfield('3');
                        }

                        $desc = $address;
                        if ($descSubField) {
                            $desc = $descSubField->getData();
                        }
                        // Is there a description?  If not, just use the URL itself.

                        $retVal[] = array('url' => $address, 'desc' => $desc);
                    }
                }
            }
        }

        return $retVal;
    }


    /**
     * Return an array of associative URL arrays with one or more of the following
     * keys:
     *
     * <li>
     *   <ul>desc: URL description text to display (optional)</ul>
     *   <ul>url: fully-formed URL (required if 'route' is absent)</ul>
     *   <ul>route: VuFind route to build URL with (required if 'url' is absent)</ul>
     *   <ul>routeParams: Parameters for route (optional)</ul>
     *   <ul>queryString: Query params to append after building route (optional)</ul>
     * </li>
     * the URLs consider restrictions by unions and tags defined by the client of this functionality
     * @return array
     */
    public function getExtendedURLs($globalunions = array(),
                                    $tags = array())
    {

        $retVal = array();

        // Which fields/subfields should we check for URLs?
        $fieldsToCheck = array(
            '856' => array('u', '3'), // Standard URL
            '956' => array('u', 'y','B'), // Standard URL
            //'555' => array('a')         // Cumulative index/finding aids
        );

        $tFieldsToCheck = array();
        if (!empty($tags)) {
            foreach($tags as $tag) {
                if (strcmp($tag, '856') == 0) {
                    $tFieldsToCheck['856'] = array('u','3');
                } elseif (strcmp($tag, '956') == 0) {
                    $tFieldsToCheck['956'] = array('u','y','B');
                }
            }
        }

        if (!empty($tFieldsToCheck)) {
            $fieldsToCheck = $tFieldsToCheck;
        }



        foreach ($fieldsToCheck as $field => $subfields) {
            $urls = $this->marcRecord->getFields($field);
            if ($urls) {
                foreach ($urls as $url) {
                    // Is there an address in the current field?
                    $address = $url->getSubfield('u');
                    if ($address) {
                        $address = $address->getData();

                        $tagDataField = $url->getTag();

                        if (strcmp($tagDataField, '856') == 0) {

                            $descSubField = $url->getSubField('3');

                        } else {
                            //it has to be 956 -> now we could have a union restriction

                            $union = $url->getSubfield('B')->getData();
                            //limited to specific networks?
                            if (sizeof($globalunions) > 0 && !in_array($union, $globalunions)) {
                                continue;
                            }


                            $descSubField = $url->getSubField('y');

                        }

                        $desc = $address;
                        if ($descSubField) {
                            $desc = $descSubField->getData();
                        }
                        // Is there a description?  If not, just use the URL itself.

                        $retVal[] = array('url' => $address, 'desc' => $desc);
                    }
                }
            }
        }

        return $retVal;
    }




    /*
     *
     */
    public function getLocalValues($localunions = array(),
                                   $localtags = array(),
                                   $indicators = array(),
                                   $subfields = array())
    {


        $retValues = array();

        $localValues = $this->marcRecord->getFields('950');
        if ($localValues) {
            foreach ($localValues as $localValue) {
                // what are tags and source code?

                $ts = $localValue->getSubfield('B');
                if (empty($ts)) continue;

                $union = $localValue->getSubfield('B')->getData();
                //limited to specific networks?
                if (sizeof($localunions) > 0 && !in_array($union, $localunions)) {
                    continue;
                }


                $ts = $localValue->getSubfield('P');
                if (empty($ts)) continue;

                $tLocalTagInSubField = $localValue->getSubfield('P');
                $tLocalTagValue = $tLocalTagInSubField->getData();
                //not the requested localTag?
                if (sizeof($localtags) > 0 && !in_array($tLocalTagValue, $localtags)) {
                    continue;
                }


                //any Indicator rules?
                if (sizeof($indicators) > 0) {
                    for ($i = 0; $i <= 1; $i++) {

                        $ts = $localValue->getSubfield('E');
                        if (empty($ts)) continue 2;

                        $t = $localValue->getSubfield('E')->getData();
                        $indicator = substr($t, $i, 1);
                        if ($indicator !== $indicators[$i]) {

                            continue 2;
                        }
                    }
                }

                $tlocalTagReturn = array();
                $tSubfields = array();
                $tlocalTagReturn['localtag'] = $tLocalTagValue;
                $tlocalTagReturn['localunion'] = $union;

                if (sizeof($subfields) > 0) {

                    foreach ($subfields as $subfield) {

                        $tLocalValueSubField = $localValue->getSubField($subfield);
                        //is there a value for the requested subfield?
                        if (!empty ($tLocalValueSubField)) {
                            $tCode = $tLocalValueSubField->getCode();
                            $tSubfields[$tCode] = $tLocalValueSubField->getData();
                        }
                    }

                } else {
                    foreach ($localValue->getSubFields() as $subfield) {

                        $tCode = $subfield->getCode();
                        $tSubfields[$tCode] = $subfield->getData();
                    }
                }

                $tlocalTagReturn['subfields'] = $tSubfields;
                $retValues[] = $tlocalTagReturn;

            }
        }
        return $retValues;
    }


    /**
     * Returns one of three things:
     * a full URL to a thumbnail preview of the record
     * if an image is available in an external system; an array of parameters to
     * send to VuFind's internal cover generator if no fixed URL exists; or false
     * if no thumbnail can be generated.
     *
     * extended from SolrDefault
     *
     * @param string $size Size of thumbnail (small, medium or large -- small is
     * default).
     *
     * @return string|array|bool
     */
    public function getThumbnail($size = 'small')
    {
        if ($isbn = $this->getCleanISBN()) {
            return array('isn' => $isbn, 'size' => $size);
        } elseif ($path = $this->getThumbnail_956_1()) {
            return $path;
        } elseif ($path = $this->getThumbnail_856_1()) {
            return $path;
        } elseif ($path = $this->getThumbnail_erara()) {
            return $path;
        }

        return false;
    }

    /**
     * get thumbnail link from 956, cases I and II (see wiki documentation)
     *
     * @return string
     */

    protected function getThumbnail_956_1()
    {
        $thumbnailURL = null;

        $fields = $this->getMarcSubFieldMaps(956, array(
                'B' => 'union',
                'C' => 'ADM',
                'D' => 'library',
                'a' => 'institution',
                'u' => 'URL',
                'd' => 'directory',
                'f' => 'filename',
                'q' => 'type',
                'x' => 'usage',
                'y' => 'description',
            ));
        if (!$fields) {
            return array();
        }

        foreach ($fields as $field) {
            if ($field['union'] === 'IDSBB' || $field['union'] === 'IDSLU') {
                if (preg_match('/Vorschau zum Bild|Porträt|Bild$/', $field['description'])) {
                    $thumbnailURL = 'https://externalservices.swissbib.ch/services/ImageTransformer?imagePath='
                    . $field['URL']
                        . '&scale=1&reqServicename=ImageTransformer';
                }
        } elseif ($field['union'] === 'SGBN' && mb_strtoupper($field['type']) === 'JPG') {
                $dirpath = preg_replace('/^.*sgb50/', '', $field['directory']);
            $dirpath = empty($dirpath) ? $dirpath : substr($dirpath, 1) . '/';
            $thumbnailURL = 'https://externalservices.swissbib.ch/services/ImageTransformer?imagePath=http://aleph.sg.ch/adam/'
                . $dirpath
                . $field['filename']
                . '&scale=1';
            } elseif ($field['union'] === 'BGR' && mb_strtoupper($field['type']) === 'JPG') {
                $dirpath = substr($field['directory'], 29);
            $thumbnailURL = 'https://externalservices.swissbib.ch/services/ImageTransformer?imagePath=http://aleph.gr.ch/adam/'
                . $dirpath . '/'
                . $field['filename']
                . '&scale=1';
            }
            elseif (isset($field['ADM']) &&  $field['ADM'] === 'ZAD50') {
                if (array_key_exists('directory',$field) && preg_match('/^.*thumbnail/', $field['directory'])) {
                    $dirpath = preg_replace('/^.*thumbnail/', '', $field['directory']);
                    $dirpath = empty($dirpath) ? $dirpath : substr($dirpath, 1) . '/';
                    $thumbnailURL = 'https://externalservices.swissbib.ch/services/ImageTransformer?imagePath=http://opac.nebis.ch/thumb_zb/'
                    . $dirpath
                    . $field['filename']
                        . '&scale=1';
                }
            }
            elseif (isset($field['institution']) &&  $field['institution'] === 'E45' && $field['usage'] === 'VIEW') {
                $thumbnailURL = 'https://externalservices.swissbib.ch/services/ImageTransformer?imagePath='
                . $field['URL']
                    . '&scale=1&reqServicename=ImageTransformer';
            }
            elseif (isset($field['institution']) && $field['institution'] === 'ECOD' && $field['usage'] === 'THUMBNAIL') {
                $thumbnailURL = 'https://externalservices.swissbib.ch/services/ImageTransformer?imagePath='
                    . $field['URL']
                    . '&scale=1&reqServicename=ImageTransformer';
            }
        }
        return $thumbnailURL;
    }

    /**
     * get thumbnail link from 856 (see wiki documentation)
     *
     * @return string
     */

    protected function getThumbnail_856_1()
    {
        $fields = $this->get950();
        if (!$fields) {
            return array();
        }
        foreach ($fields as $field) {
            if (!isset($field['union'])) continue;

            if ($field['union'] === 'RERO' && $field['tag'] === '856') {
                if (preg_match('/^.*v_bcu\/media\/images/', $field['sf_u'])) {
                    return 'https://externalservices.swissbib.ch/services/ImageTransformer?imagePath='
                    . $field['sf_u']
                    . '&scale=1';
                } elseif (preg_match('/^.*bibliotheques\/iconographie/', $field['sf_u'])) {
                    return 'https://externalservices.swissbib.ch/services/ImageTransformer?imagePath='
                    /*return 'https://externalservices.swissbib.ch/services/ProtocolWrapper?targetURL='*/
                    . $field['sf_u']
                    . '&scale=1';
                }
            } elseif ($field['union'] === 'CCSA' && $field['tag'] === '856') {
                $URL_thumb = preg_replace('/hi-res.cgi/', 'get_thumb.cgi', $field['sf_u']);
                return 'https://externalservices.swissbib.ch/services/ImageTransformer?imagePath='
                . $URL_thumb
                . '&scale=1';
            } elseif ($field['union'] === 'CHARCH' && $field['tag'] === '856') {
                $thumb_URL = preg_replace('/SIZE=10/', 'SIZE=30', $field['sf_u']);
                $URL_thumb = preg_replace('/http/', 'https', $thumb_URL);
                return $URL_thumb;
            }
            //return 'https://externalservices.swissbib.ch/services/ImageTransformer?imagePath='
            //. $URL_thumb
            //. '&scale=1';
        }
    }

    /**
     * get thumbnail link from e-rara-DOI (see wiki documentation)
     *
     * @return string
     */

    protected function getThumbnail_erara()
    {
        $field = $this->getDOIs();
        if (!empty($field) && preg_match('/^.*e-rara/', $field['0'])) {
            $URL_thumb = 'http://www.e-rara.ch/titlepage/doi/'
                . $field['0']
                . '/128';
            return 'https://externalservices.swissbib.ch/services/ImageTransformer?imagePath='
            . $URL_thumb
            . '&scale=1';
        }
        return false;
    }

    /**
     * Get fully mapped field 956 (local links, ADAM objects)
     *
     * @return array
     *
     */
    protected function get956()
    {
        return $this->getMarcSubFieldMap(956, array(
            'B' => 'union',
            'C' => 'ADM',
            'D' => 'library',
            'a' => 'institution',
            'u' => 'URL',
            'd' => 'directory',
            'f' => 'filename',
            'q' => 'type',
            'x' => 'usage',
            'y' => 'description',
        ));
    }

    /**
     * Get partially mapped field 950 (Parking-field)
     *
     * @return array
     *
     */
    protected function get950()
    {
        return $this->getMarcSubFieldMaps(950, array(
            'B' => 'union',
            'P' => 'tag',
            'a' => 'sf_a',
            'u' => 'sf_u',
            'z' => 'sf_z',
            '3' => "sf_3",
        ));
    }

    /**
     * Get translated formats
     *
     * @return    String[]
     */
    protected function getFormatsTranslated()
    {
        $formats = $this->getFormatsRaw();
        $translator = $this->getTranslator();

        foreach ($formats as $index => $format) {
            $formats[$index] = $translator->translate($format);
        }

        return $formats;
    }


    /**
     * Get ISMN (International Standard Music Number)
     *
     * @return array
     */
    public function getISMNs()
    {
        return isset($this->fields['ismn_isn_mv']) && is_array($this->fields['ismn_isn_mv']) ?
            $this->fields['ismn_isn_mv'] : array();
    }


    /**
     * Get DOI (Digital Object Identifier)
     *
     * @return array
     */
    public function getDOIs()
    {
        return isset($this->fields['doi_isn_mv']) && is_array($this->fields['doi_isn_mv']) ?
            $this->fields['doi_isn_mv'] : array();
    }


    /**
     * Get URN (Uniform Resource Name)
     *
     * @return array
     */
    public function getURNs()
    {
        return isset($this->fields['urn_isn_mv']) && is_array($this->fields['urn_isn_mv']) ?
            $this->fields['urn_isn_mv'] : array();
    }


    /**
     * Get formats modified to work with openURL
     * Formats: Book (is default), Journal, Article
     *
     * @return    String[]
     */
    public function getFormatsOpenUrl()
    {
        $formats = $this->getFormatsRaw();
        $found = false;
        $mapping = array(
            'XK01' => 'Article',
            'XK02' => 'Book',
            'XR0300' => 'Journal',
        );

        // Check each format for all patterns
        foreach ($formats as $rawFormat) {
            foreach ($mapping as $pattern => $targetFormat) {
                // Test for begin of string
                if (stristr($rawFormat, $pattern)) {
                    $formats[] = $targetFormat;
                    $found = true;
                    break 2; // Stop both loops
                }
            }
        }

        // Fallback: Book
        if ($found == false) {
            $formats[] = 'Book';
        }

        return $formats;
    }


    /**
     * Get raw formats as provided by the basic driver
     * Wrap for getFormats() because it's overwritten in this driver
     *
     * @return    String[]
     */
    public function getFormatsRaw()
    {
        return parent::getFormats();
    }


    /**
     * Returns as array to use same template with foreach as normally
     * @return array
     */
    public function getMostSpecificFormat()
    {
        if (isset($this->fields["format_str_mv"])) {
            $formatsRaw = $this->fields["format_str_mv"];
            natsort($formatsRaw);
            $formatsRaw = array_values(array_reverse($formatsRaw));

            return array($formatsRaw[0]);

        } else {
            return [];
        }

    }


    /**
     * Get years and datetype from field 008
     * format in calling functions for display and/or output
     *
     * @return  Array
     */
    public function getPublicationDates()
    {
        // Get field 008 fixed field code
        $code = $this->marcRecord->getField('008')->getData();

        // Get parts
        $dateType = substr($code, 6, 1);
        $year1 = substr($code, 7, 4);
        $year2 = substr($code, 11, 4);

        return array($dateType, $year1, $year2);
    }


    /**
     * Get primary author
     *
     * @param    Boolean $asString
     * @return    Array|String
     */
    public function getPrimaryAuthor($asString = true)
    {
        $data = $this->getMarcSubFieldMap(100, $this->personFieldMap);

        if ($asString) {
            $name = isset($data['name']) ? $data['name'] : '';
            $name .= isset($data['forname']) ? ', ' . $data['forname'] : '';

            return trim($name);
        }

        return $data;
    }


    /**
     * Get list of secondary authors data
     *
     * @param    Boolean $asString
     * @return    Array[]
     */
    public function getSecondaryAuthors($asString = true)
    {
        $authors = $this->getMarcSubFieldMaps(700, $this->personFieldMap);

        if ($asString) {
            $stringAuthors = array();

            foreach ($authors as $author) {
                $name = isset($author['name']) ? $author['name'] : '';
                $forename = isset($author['forname']) ? $author['forname'] : '';
                $stringAuthors[] = trim($name . ', ' . $forename);
            }

            $authors = $stringAuthors;
        }

        return $authors;
    }

    /**
     * @param bool $asString
     *
     * @return array|\Array[]
     */

    public function getCorporationNames($asString = true)
    {
        $unit = $units = $corporation = $corporations = $stringCorporations = null;
        $corporations = $this->getAddedCorporateNames();
        //$corporations[] = $this->getMainCorporateName();

        if ($asString) {
            $stringCorporations = [];

            foreach ($corporations as $corporation) {
                $name = isset($corporation['name']) ? rtrim($corporation['name'], '.') : '';
                if (isset($corporation['unit'])) {
                    foreach ($corporation['unit'] as $unit) {
                        $units = '. ' . $unit;
                    }
                    $stringCorporations[] = trim($name . $units);
                }
                else $stringCorporations[] = $name;
            }
                return $stringCorporations;
            }
        return false;
    }


    /**
     * Get corporate name (authors)
     *
     * @todo    Implement or remove note
     * @note    exclude: if $l == fre|eng
     * @return    Array[]
     */
    public function getMainCorporateName()
    {
        return $this->getMarcSubFieldMap(110, $this->corporationFieldMap);
    }


    /**
     * Get added corporate names
     *
     * @return    Array[]
     */
    public function getAddedCorporateNames()
    {
        return $this->getMarcSubFieldMaps(710, $this->corporationFieldMap);
    }


    /**
     * Get sub title
     *
     * @param    Boolean $full Get full field data. Else only field c is fetched
     * @return    String|String[]
     */
    public function getTitleStatement($full = false)
    {
        if ($full) {
            return $this->getMarcSubFieldMap(245, array(
                'a' => 'title',
                'b' => 'title_remainder',
                'c' => 'statement_responsibility',
                'f' => 'inclusive_dates',
                'g' => 'bulk_dates',
                'h' => 'medium',
                '_k' => 'form',
                '_n' => 'parts_amount',
                '_p' => 'parts_name',
                's' => 'version'
            ));
        } else {
            return parent::getTitleStatement();
        }
    }


    /**
     * Get edition
     *
     * @return    String
     */
    public function getEdition()
    {
        $data = $this->getMarcSubFieldMaps(250, array(
                'a' => 'statement',
                'b' => 'remainder',
            ));

        if (!empty($data)) {
            foreach ($data as $field) {
                if (isset($field['statement']))
                {
                    $string = $field['statement'];
                }
                if (isset($field['remainder']))
                {
                    $string .= ' / ' . $field['remainder'];
                }
            }
            return $string;
        }
        return false;
    }


    /**
     * Get alternative title
     *
     * @return array
     */
    public function getAltTitle()
    {
        return $this->getFieldArray('246', '247');
    }

    /**
     * get Cartographic Mathematical Data
     *
     * @return string
     */
    public function getCartMathData()
    {
        $data = $this->getMarcSubFieldMaps(255, array(
                'a' => 'scale',
                'b' => 'projection',
                'c' => 'coordinates',
                'd' => 'zone',
                'e' => 'equinox',
            ));

        if (!empty($data)) {
            foreach ($data as $field) {
                $string = '';

                if (isset($field['scale']))
                {
                    $string = $field['scale'];
                }
                if (isset($field['projection']))
                {
                    $string .= '; ' . $field['projection'];
                }
                if (isset($field['coordinates']))
                {
                    $string .= ' - ' . $field['coordinates'];
                }
            }
            return $string;
        }

        return false;
    }


    /**
     * Get dissertation notes for the record.
     *
     * @return array
     */
    public function getDissertationNotes()
    {
        return $this->getFieldArray('502');
    }

    /**
     * Get original title from IDS MARC
     *
     * @return array
     */

    public function getOriginalTitle($asStrings = true)
    {
        $data = $this->getMarcSubFieldMaps(509, array(
            'a' => 'title',
            'n' => 'count',
            'p' => 'worktitle',
            'r' => 'author',
            'i' => 'addtext',
        ));
        if ($asStrings) {
            $strings = array();

            foreach ($data as $origtitle) {
                $string = '';

                if (isset($origtitle['title'])) {
                    $string = str_replace('@', '', $origtitle['title']);
                }
                if (isset($origtitle['count'])) {
                    $string .= '(' . $origtitle['count'] . ')';
                }
                if (isset($origtitle['author'])) {
                    $string .= ' / ' . $origtitle['author'];
                }
                if (isset($origtitle['addtext'])) {
                    $string .= '. - ' . $origtitle['addtext'];
                }

                $strings[] = trim($string);
            }

            $data = $strings;
        }
        return $data;
    }

    /**
     * Get Dates of Publication and/or Sequential Designation (field 362)
     *
     * return @array
     */
    public function getContResourceDates()
    {
        return $this->getFieldArray('362');
    }

    /**
     * Get citation / reference note for the record
     *
     * @return array
     */
    public function getCitationNotes()
    {
        return $this->getFieldArray('510');
    }

    /**
     * Get participant or performer note for the record.
     *
     * @return array
     */
    public function getPerformerNote()
    {
        return $this->getFieldArray('511');
    }

    /**
     * Get original version note for the record.
     *
     * @return array
     */
    public function getOriginalVersionNotes()
    {
        return $this->getFieldArray('534', array('p', 't', 'c'));
    }

    /**
     * Get Copy and Version Identification Note
     *
     * @return array
     */
    public function getCopyIdentificationNote()
    {
        return $this->getFieldArray('562', array('c'));
    }

    /**
     * Get original version note for the record (MARC21: field 562) and item-specific note
     * for the record (swissbib MARC: field 590)
     *
     * @return array
     */
    public function getCopyNotes()
    {
        $f562 = $this->getFieldArray('562', array('c'));
        $f590 = $this->getFieldArray('590');

        $copynotes = array_merge_recursive($f562, $f590);

        return $copynotes;
    }


    /**
     * get group-id from solr-field to display FRBR-Button
     *
     * @return    String|Number
     */
    public function getGroup()
    {
        return isset($this->fields['groupid_isn_mv']) ?  $this->multiValuedFRBRField ? $this->fields['groupid_isn_mv'][0] : $this->fields['groupid_isn_mv'] : '';
    }


    /**
    * Get institution codes
    *
    * @return array
    */
    public function getInstitutions($extended = false)
    {
        $institutions = array();

        if (isset($this->fields['institution']) && is_array($this->fields['institution'])) {
            $institutions = $this->fields['institution'];

            if ($extended) {
                foreach($institutions as $key => $institution) {
                    $institutions[$key] = array(
                        'institution' => $institution,
                        'group' => $this->getHoldingsHelper()->getGroup($institution)
                    );
                }
            }
        }

        return $institutions;
    }

    /**
     * Get structured subject vocabularies from predefined fields
     * Extended version of getAllSubjectHeadings()
     *
     * $fieldIndexes contains keys of fields to check
     * $vocabConfigs contains checks for vocabulary detection
     *
     * $vocabConfigs:
     * - ind: Value for indicator 2 in tag
     * - field: sub field 2 in tag
     * - fieldsOnly: Only check for given field indexes
     * - detect: The vocabulary key is defined in sub field 2. Don't use the key in the config (only used for local)
     *
     * Expected result:
     * [
     *        gnd => [
     *            600 => [{},{},{},...]
     *            610 => [{},{},{},...]
     *            620 => [{},{},{},...]
     *        ],
     *    rero => [
     *            600 => [{},{},{},...]
     *            610 => [{},{},{},...]
     *            620 => [{},{},{},...]
     *        ]
     * ]
     * {} is an assoc array which contains the field data
     *
     * @see    getAllSubjectHeadings
     * @param    Boolean $ignoreControlFields Ignore control fields 0 and 2
     * @return    Array[]
     */
    public function getAllSubjectVocabularies($ignoreControlFields = false)
    {
        $subjectVocabularies = array();
        $fieldIndexes = array(600, 610, 611, 630, 648, 650, 651, 655, 656, 690, 691);
        $vocabConfigs = array(
            'lcsh' => array(
                'ind' => 0
            ),
            'mesh' => array(
                'ind' => 2
            ),
            'unspecified' => array(
                'ind' => 4
            ),
            'gnd' => array(
                'ind' => 7,
                'field' => 'gnd'
            ),
            'rero' => array(
                'ind' => 7,
                'field' => 'rero'
            ),
            'idsbb' => array(
                'ind' => 7,
                'field' => 'idsbb'
            ),
            'idszbz' => array(
                'ind' => 7,
                'field' => 'idszbz'
            ),
            /*'idslu'       => array(
                'ind'   => 7,
                'field' => 'idslu'
            ),
            'bgr'         => array(
                'ind'   => 7,
                'field' => 'bgr'
            ),*/
            'sbt' => array(
                'ind' => 7,
                'field' => 'sbt'
            ),
            'jurivoc' => array(
                'ind' => 7,
                'field' => 'jurivoc'
            ),
            /* only works for one indicator (test case)
               implement with new CBS-data (standardised MARC, not IDSMARC)
            */
            'local' => array(
                'ind' => 7,
                'fieldsOnly' => array(690),
                'detect' => false // extract vocabulary from sub field 2
            ),
        );
        $fieldMapping = array(
            'a' => 'a',
            '_b' => 'b',
            'c' => 'c',
            'd' => 'd',
            'e' => 'e',
            'f' => 'f',
            'g' => 'g',
            'h' => 'h',
            't' => 't',
            '_v' => 'v',
            '_x' => 'x',
            '_y' => 'y',
            '_z' => 'z'
        );

        // Add control fields to mapping list
        if (!$ignoreControlFields) {
            $fieldMapping += array(
                '0' => '0',
                '2' => '2'
            );
        }

        // Iterate over all indexes to check the available fields
        foreach ($fieldIndexes as $fieldIndex) {
            $indexFields = $this->getMarcFields($fieldIndex);

            // iterate over all fields found for the current index
            foreach ($indexFields as $indexField) {
                // check all vocabularies for matching
                foreach ($vocabConfigs as $vocabKey => $vocabConfig) {
                    $fieldData = false;
                    $useAsVocabKey = $vocabKey;

                    // Are limited fields set in config
                    if (isset($vocabConfig['fieldsOnly']) && is_array($vocabConfig['fieldsOnly'])) {
                        if (!in_array($fieldIndex, $vocabConfig['fieldsOnly'])) {
                            continue; // Skip vocabulary if field is not in list
                        }
                    }

                    if (isset($vocabConfig['ind']) && $indexField->getIndicator(2) == (string)$vocabConfig['ind']) {
                        if (isset($vocabConfig['field'])) { // is there a field check required?
                            $subField2 = $indexField->getSubfield('2');
                            if ($subField2 && $subField2->getData() === $vocabConfig['field']) { // Check field
                                // sub field 2 matches the config
                                $fieldData = $this->getMappedFieldData($indexField, $fieldMapping, false);
                            }
                        } else { // only indicator required, add data
                            $fieldData = $this->getMappedFieldData($indexField, $fieldMapping, false);
                        }
                    }

                    // Found something? Add to list, stop vocab check and proceed with next field
                    if ($fieldData) {
                        // Is detect option set, replace vocab key with value from sub field 2 if present
                        if (isset($vocabConfig['detect']) && $vocabConfig['detect']) {
                            $subField2 = $indexField->getSubfield('2');
                            if ($subField2) {
                                $useAsVocabKey = $subField2->getData();
                            }
                        }

                        $subjectVocabularies[$useAsVocabKey][$fieldIndex][] = $fieldData;
                        break; // Found vocabulary, stop search
                    }
                }
            }
        }

        return $subjectVocabularies;
    }

    /**
     * Get host item entry
     *
     * @return    Array
     */
    public function getHostItemEntry()
    {
        return $this->getMarcSubFieldMaps(773, array(
            'd' => 'place',
            't' => 'title',
            'g' => 'related',
        ));
    }


    /**
     * Get the publishers of the record.
     *
     * @return array
     */
    public function getPublishers()
    {
        return $this->getFieldArray('260', array('b'));
    }

    /**
     * Get human readable publication dates for display purposes (may not be suitable
     * for computer processing -- use getPublicationDates() for that).
     * still using coded data for display in swissbib, reason: consistency
     *
     * @return array
     */
    public function getHumanReadablePublicationDates()
    {
        $codeddata = array();
        $codeddata = $this->getPublicationDates();

        if (!is_array($codeddata) || sizeof($codeddata) == 0) {
            return '';
        }

        if (is_array($codeddata) && sizeof($codeddata) == 1) {
            return $codeddata[0];
        }

        $retVal = array();

        $dateType = $codeddata[0];
        $year1    = $codeddata[1];
        $year2    = $codeddata[2];

        switch ($dateType)
        {
            case 's':
            case 't':
            case 'n':
            case 'e':
                $retVal[0] = $year1;
                break;

            case 'c':
            case 'u':
                $retVal[0] = $year1 . '-';
                break;

            case 'd':
                $retVal[0] = $year1 . '-' . $year2;
                break;

            case 'p':
            case 'r':
                $retVal[0] = $year1 . ' [' . $year2 . ']';
                break;

            case 'q':
                if ($year2 === '9999') {
                    $retVal[0] = $year1;
                }
                elseif ($year2 != '9999') {
                    $retVal[0] = $year1 . ' / ' . $year2;
                }
                break;

            case 'm':
                if ($year2 === '9999') {
                    $retVal[0] = $year1 . '-';
                }
                elseif ($year2 != '9999') {
                    $retVal[0] = $year1 . '-' . $year2;
                }
                break;

            case 'i':
                if ($year1 === $year2) {
                    $retVal[0] = $year1;
                }
                if ($year2 === '9999') {
                    $retVal[0] = $year1 . '-';
                }
                else {
                    $retVal[0] = $year1 . '-' . $year2;
                }
                break;
        }
        $retVal[0] = str_replace('u', '?', $retVal);
        return $retVal[0];
    }

    /**
     * Get physical description out of the MARC record
     *
     * @param    Boolean $asStrings
     * @return    Array[]|String[]
     */
    public function getPhysicalDescriptions($asStrings = true)
    {
        $descriptions = $this->getMarcSubFieldMaps(300, array(
            '_a' => 'extent',
            'b' => 'details',
            '_c' => 'dimensions',
            'd' => 'material_single',
            '_e' => 'material_multiple',
            '_f' => 'type',
            '_g' => 'size',
            '3' => 'appliesTo'
        ));

        if ($asStrings) {
            $strings = array();
            foreach ($descriptions as $description) {
                if (isset($description['extent']) && isset($description['extent'][0])) {
                    $strings[] = $description['extent'][0];
                }
            }
            $descriptions = $strings;
        }

        return $descriptions;
    }

    /**
     * Get Dates of Publication and/or Sequential Designation (field 362)
     *
     * return @array
     */
    public function getDateSpan()
    {
        return $this->getFieldArray('362');
    }

    /**
     * Get unions
     *
     * @return    String[]
     */
    public function getUnions()
    {
        return isset($this->fields['union']) ? $this->fields['union'] : array();
    }

    /**
     * Get online status
     *
     * @return Boolean
     */

    public function getOnlineStatus()
    {
        $filter = array_key_exists('filter_str_mv', $this->fields) ? $this->fields['filter_str_mv'] : array();
        return in_array('ONL', $filter)  ? true : false;
    }


    /**
     * Get short title
     * Override base method to assure a string and not an array
     * as long as title_short is multivalued=true in solr (necessary because of faulty data)
     *
     * @return    String
     */
    public function getShortTitle()
    {
        $shortTitle = parent::getShortTitle();

        return is_array($shortTitle) ? reset($shortTitle) : $shortTitle;
    }


    /**
     * Get title
     *
     * @return    String
     */
    public function getTitle()
    {
        $title = parent::getTitle();

        return is_array($title) ? reset($title) : $title;
    }

    /**
     * Get is_hierarchy_title
     *
     * @return string
     */
    public function getIs_hierarchy_title() {
        return isset($this->fields['is_hierarchy_title']) ? $this->fields['is_hierarchy_title'] : $this->getTitle();
    }


    /**
     * Get holdings data
     *
     * @param    String $institutionCode
     * @param    Boolean $extend
     * @return    Array|Boolean
     */
    public function getInstitutionHoldings($institutionCode, $extend = true)
    {
        return $this->getHoldingsHelper()->getHoldings($this, $institutionCode, $extend);
    }


    /**
     * Get holdings structure without item details
     *
     * @return Array[]|bool
     */
    public function getHoldingsStructure()
    {
        return $this->getHoldingsHelper()->getHoldingsStructure();
    }


    /**
     * Get hierarchy type
     * Directly use driver config
     *
     * @return bool|string
     */

    public function getHierarchyType()
    {
        $type = parent::getHierarchyType();

        return $type ? $type : $this->mainConfig->Hierarchy->driver;
    }


    /**
     * Get marc field
     *
     * @param    Integer $index
     * @return    \File_MARC_Data_Field|Boolean
     */
    protected function getMarcField($index)
    {
        $index = sprintf('%03d', $index);

        return $this->marcRecord->getField($index);
    }


    /**
     * Get marc fields
     * Multiple values are possible for the field
     *
     * @param    Integer $index
     * @return    \File_MARC_Data_Field[]|\File_MARC_List
     */
    protected function getMarcFields($index)
    {
        $index = sprintf('%03d', $index);

        return $this->marcRecord->getFields($index);
    }


    /**
     * Get items of a field as named map (array)
     * Use this method if the field is (N)ot(R)epeatable
     *
     * @param       $index
     * @param array $fieldMap
     * @return array
     */
    protected function getMarcSubFieldMap($index, array $fieldMap)
    {
        $index = sprintf('%03d', $index);
        $subFieldValues = array();
        $field = $this->marcRecord->getField($index);

        if ($field) {
            $subFieldValues = $this->getMappedFieldData($field, $fieldMap);
        }

        return $subFieldValues;
    }


    /**
     * Get items of a field (which exists multiple times) as named map (array)
     * Use this method if the field is (R)epeatable
     *
     * @param    Integer $index
     * @param    Array $fieldMap
     * @param    Boolean $includeIndicators
     * @return    Array[]
     */
    protected function getMarcSubFieldMaps($index, array $fieldMap, $includeIndicators = true)
    {
        $subFieldsValues = array();
        $fields = $this->marcRecord->getFields($index);

        foreach ($fields as $field) {
            $subFieldsValues[] = $this->getMappedFieldData($field, $fieldMap, $includeIndicators);
        }

        return $subFieldsValues;
    }


    /**
     * Convert sub fields to array map
     *
     * @param    \File_MARC_Data_Field $field
     * @param    Array $fieldMap
     * @param    Boolean $includeIndicators Add the two indicators to the field list
     * @return    Array
     */
    protected function getMappedFieldData($field, array $fieldMap, $includeIndicators = true)
    {
        $subFieldValues = array();

        if ($includeIndicators) {
            $subFieldValues['@ind1'] = $field->getIndicator(1);
            $subFieldValues['@ind2'] = $field->getIndicator(2);
        }

        foreach ($fieldMap as $code => $name) {
            if (substr($code, 0, 1) === '_') { // Underscore means repeatable
                $code = substr($code, 1); // Remove underscore
                /** @var \File_MARC_Subfield[] $subFields */
                $subFields = $field->getSubfields((string)$code);

                if (sizeof($subFields)) {
                    //$subFieldValues[$name] = array();
                    $i = 1;
                    foreach ($subFields as $subField) {
                        $subFieldValues[$i . $name] = $subField->getData();
                        $i++;
                    }
                }
            } else { // Normal single field
                $subField = $field->getSubfield((string)$code);

                if ($subField) {
                    $subFieldValues[$name] = $subField->getData();
                }
            }
        }

        return $subFieldValues;
    }


    /**
     * Get fields data without mapping. Keep original order of subfields
     *
     * @param    Integer $index
     * @return    Array[]
     */
    protected function getMarcSubfieldsRaw($index)
    {
        /** @var \File_MARC_Data_Field[] $fields */
        $fields = $this->marcRecord->getFields($index);
        $fieldsData = array();

        foreach ($fields as $field) {
            $tempFieldData = array();

            /** @var \File_MARC_Subfield[] $subfields */
            $subfields = $field->getSubfields();

            foreach ($subfields as $subfield) {
                $tempFieldData[] = array(
                    'tag' => $subfield->getCode(),
                    'data' => $subfield->getData()
                );
            }

            $fieldsData[] = $tempFieldData;
        }

        return $fieldsData;
    }


    /**
     * Get value of a sub field
     *
     * @param    Integer $index
     * @param    String $subFieldCode
     * @return    String|Boolean
     */
    protected function getSimpleMarcSubFieldValue($index, $subFieldCode)
    {
        $field = $this->getMarcField($index);

        if ($field) {
            $subField = $field->getSubfield($subFieldCode);

            if ($subField) {
                return $subField->getData();
            }
        }

        return false;
    }


    /**
     * Get value of a field
     *
     * @param    Integer $index
     * @return    String|Boolean
     */
    protected function getSimpleMarcFieldValue($index)
    {
        /** @var \File_MARC_Control_Field $field */
        $field = $this->getMarcField($index);

        return $field ? $field->getData() : false;
    }


    /**
     * Get initialized holdings helper
     *
     * @return    HoldingsHelper
     */
    protected function getHoldingsHelper()
    {
        if (!$this->holdingsHelper) {

            //core record driver in itself doesn't support implmentation of ServiceLocaterAwareInterface with latest merge
            //alternative to the current solution:
            //we implement this Interface by ourselve
            //at the moment I don't know what's the role of the hierachyDriverManager and if it's always initialized
            //ToDo: more analysis necessary!
            //$holdingsHelper = $this->getServiceLocator()->getServiceLocator()->get('Swissbib\HoldingsHelper');
            /** @var HoldingsHelper $holdingsHelper */
            $holdingsHelper = $this->getServiceLocator()->get('Swissbib\HoldingsHelper');

            $holdingsData = isset($this->fields['holdings']) ? $this->fields['holdings'] : '';

            $holdingsHelper->setData($this->getUniqueID(), $holdingsData);

            $this->holdingsHelper = $holdingsHelper;
        }

        return $this->holdingsHelper;
    }


    /**
     * Helper to get service locator
     *
     * @return    ServiceLocatorInterface
     */
    public function getServiceLocator()
    {
        return $this->hierarchyDriverManager->getServiceLocator();
    }


    /**
     * Get translator
     *
     * @return    Translator
     */
    /*
    protected function getTranslator()
    {
        return $this->getServiceLocator()->get('VuFind/Translator');
    }
    */


    /**
     * Get stop words from 909 fields
     *
     * @return    String[]
     */
    public function getLocalCodes()
    {
        $localCodes = array();
        $fieldsValues = $this->getMarcSubFieldMaps(909, array(
            'a' => 'a',
            'b' => 'b',
            'c' => 'c',
            'd' => 'd',
            'e' => 'e',
            'f' => 'f',
            'g' => 'g',
            'h' => 'h',
        ));

        foreach ($fieldsValues as $fieldValues) {
            foreach ($fieldValues as $fieldName => $fieldValue) {
                if (strpos($fieldName, '@') !== 0) {
                    $localCodes[] = $fieldValue;
                }
            }
        }

        return $localCodes;
    }


    /**
     * Get highlighted fulltext
     *
     * @return    String
     */
    public function getHighlightedFulltext()
    {
        // Don't check for highlighted values if highlighting is disabled:
        if (!$this->highlight) {
            return '';
        }

        return (isset($this->highlightDetails['fulltext'][0])) ? trim($this->highlightDetails['fulltext'][0]) : '';
    }


    /**
     * Get table of content
     * This method is used to check whether data for tab is available and the tab should be displayed
     * Differs functionally from parent as we display more information in toc.phtml
     *
     * @return    String[]
     */
    public function getTOC()
    {
        return $this->getTableOfContent() + $this->getContentSummary();
    }


    /**
     * Get table of content
     * From fields 505.g.r.t
     * The combination of the lines of defined by the order of the fields
     * Possible combinations:
     * - $g. $t / $r
     * - $g. $t
     * - $g. $r
     * - $t. $r
     * - $t
     * - $r
     *
     * Use the content of the $debugLog if something seems wrong
     *
     * @return    String[]
     */
    public function getTableOfContent()
    {
        $lines = array();
        $fieldsData = $this->getMarcSubfieldsRaw(505);
        $debugLog = array();

        foreach ($fieldsData as $fieldIndex => $field) {
            $maxIndex = sizeof($field) - 1;
            $index = 0;

            while ($index <= $maxIndex) {
                $hasNext = isset($field[$index + 1]);
                $hasTwoNext = isset($field[$index + 2]);
                $currentTag = $field[$index]['tag'];
                $currentData = $field[$index]['data'];
                $nextTag = $hasNext ? $field[$index + 1]['tag'] : null;
                $nextData = $hasNext ? $field[$index + 1]['data'] : null;
                $twoNextTag = $hasTwoNext ? $field[$index + 2]['tag'] : null;
                $twoNextData = $hasTwoNext ? $field[$index + 2]['data'] : null;

                if ($currentTag === 'g') {
                    if ($hasNext) {
                        if ($nextTag === 't') {
                            if ($hasTwoNext && $twoNextTag === 'r') { // $g. $t / $r
                                $lines[] = $currentData . '. ' . $nextData . ' / ' . $twoNextData;
                                $debugLog[$fieldIndex][] = $index . ' | $g. $t / $r';
                                $index += 3;
                            } else { // $g. $t
                                $lines[] = $currentData . '. ' . $nextData;
                                $debugLog[$fieldIndex][] = $index . ' | $g. $t';
                                $index += 2;
                            }
                        } elseif ($nextTag === 'r') { // $g. $r
                            $lines[] = $currentData . '. ' . $nextData;
                            $debugLog[$fieldIndex][] = $index . ' | $g. $r';
                            $index += 2;
                        } else {
                            // unknown order
                            $debugLog[$fieldIndex][] = $index . ' | unknown order';
                            $index += 1;
                        }
                    }
                    $index++;
                } elseif ($currentTag === 't') {
                    if ($hasNext) {
                        if ($nextTag === 'r') { // $t / $r
                            $lines[] = $currentData . ' / ' . $nextData;
                            $debugLog[$fieldIndex][] = $index . ' | $t / $r';
                            $index += 2;
                        } else { // $t
                            $lines[] = $currentData;
                            $debugLog[$fieldIndex][] = $index . ' | $t';
                            $index += 1;
                        }
                    } else { // $t
                        $lines[] = $currentData;
                        $debugLog[$fieldIndex][] = $index . ' | $t';
                        $index += 1;
                    }
                } elseif ($currentTag === 'r') { // $r
                    $lines[] = $currentData;
                    $debugLog[$fieldIndex][] = $index . ' | $r';
                    $index += 1;
                } elseif ($currentTag === 'a') { // $a
                    $lines[] = $currentData;
                    $index += 1;
                } else {
                    // unknown order
                    $debugLog[$fieldIndex][] = $index . ' | unknown order';
                    $index += 1;
                }
            }
        }

        return $lines;
    }


    /**
     * Get content summary
     * From fields 520
     *
     * @return    String[]
     */
    public function getContentSummary()
    {
        $lines = array();
        $summary = $this->getMarcSubFieldMaps(520, array(
            'a' => 'summary',
            'b' => 'expansion',
        ), false);

        // Copy into simple list
        foreach ($summary as $item) {
            if(isset($item['expansion'])) {
                $lines[] = $item['summary'] . '. ' . $item['expansion'];
            }
            else {
                $lines[] = $item['summary'];
            }

        }

        return $lines;
    }


    /**
     * Get last indexed date string for sorting
     *
     * @return    String
     */
    public function getLastIndexed()
    {
        return isset($this->fields['time_indexed']) ? $this->fields['time_indexed'] : '';
    }


    /**
     *
     *
     * @param    \File_MARC_Data_Field $field
     * @param    String $fieldIndex
     */
    protected function getFieldData($field)
    {
        // Make sure that there is a t field to be displayed:
        if ($title = $field->getSubfield('t')) {
            $title = $title->getData();
        } else {
            return false;
        }

        $linkTypeSetting = isset($this->mainConfig->Record->marc_links_link_types)
            ? $this->mainConfig->Record->marc_links_link_types : 'id,oclc,dlc,isbn,issn,title';
        $linkTypes = explode(',', $linkTypeSetting);

        $link = false;

        if (in_array('id', $linkTypes)) { // Search ID in field 9
            $linkSubfield = $field->getSubfield('9');
            if ($linkSubfield && $bibLink = $this->getIdFromLinkingField($linkSubfield)) {
                $link = array('type' => 'bib', 'value' => $bibLink);
            }
        } elseif (in_array('ctrlnum', $linkTypes)) { // Extract ctrlnum from field w, ignore the prefix
            $linkFields = $linkFields = $field->getSubfields('w');
            foreach ($linkFields as $current) {
                if (preg_match('/\(([^)]+)\)(.+)/', $current->getData(), $matches)) {
                    $link = array('type' => 'ctrlnum', 'value' => $matches[1] . $matches[2]);
                }
            }
        }

        // Found link based on special conditions, stop here
        if ($link) {
            return array(
                'title' => $this->getRecordLinkNote($field),
                'value' => $title,
                'link' => $link
            );
        }

        // Fallback to base method if no custom field found
        return parent::getFieldData($field);
    }


    /**
     * @inheritDoc
     * @note    Prevent php error for invalid index data. parent_id and sequence should contain the same amount of values which correspond
     * @return    Array
     */
    public function getHierarchyPositionsInParents()
    {
        if (isset($this->fields['hierarchy_parent_id'])
            && isset($this->fields['hierarchy_sequence'])
        ) {
            if (sizeof($this->fields['hierarchy_parent_id']) > sizeof($this->fields['hierarchy_sequence'])) {
                $this->fields['hierarchy_parent_id'] = array_slice($this->fields['hierarchy_parent_id'], 0, sizeof($this->fields['hierarchy_sequence']));
            }
        }

        return parent::getHierarchyPositionsInParents();
    }

    
    /**
     * @return bool
     */
    public function getUseMostSpecificFormat() {
        return $this->useMostSpecificFormat;
    }


    /**
     * @param boolean
     */
    public function setUseMostSpecificFormat($useMostSpecificFormat) {
        $this->useMostSpecificFormat = (boolean) $useMostSpecificFormat;
    }


    /**
     * @return bool
     */
    public function hasDescription() {
        foreach ($this->partsOfDescription as $descriptionElement) {
            $method = 'get' . $descriptionElement;
            if (method_exists($this, $method)) {
                $result = $this->$method();
                if (!empty($result)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Returns true if the record supports real-time AJAX status lookups.
     *
     * @return bool
     */
    public function supportsAjaxStatus()
    {
        return false;
    }


    /**
     * @override
     * @return array Strings representing citation formats.
     */
    public function getCitationFormats()
    {
        $solrDefaultAdapter = $this->getServiceLocator()->get('Swissbib\RecordDriver\SolrDefaultAdapter');

        return $solrDefaultAdapter->getCitationFormats();
    }


    /**
     * @return boolean
     */
    public function displayHoldings()
    {
        return true;
    }

    /**
     * @return boolean
     */
    public function displayLinks()
    {
        return false;
    }
}
