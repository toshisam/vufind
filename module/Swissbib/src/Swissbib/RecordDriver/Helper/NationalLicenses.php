<?php
/**
 * EbooksOnDemand
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
 * @category Swissbib_VuFind2
 * @package  RecordDriver_Helper
 * @author   Guenter Hipler  <guenter.hipler@unibas.ch>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://www.swissbib.org
 */
namespace Swissbib\RecordDriver\Helper;

/**
 * Build ebook links depending on institution configuration
 * Config in config_base.ini[eBooksOnDemand]
 *
 * @category Swissbib_VuFind2
 * @package  RecordDriver_Helper
 * @author   Guenter Hipler <guenter.hipler@unibas.ch>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */
class NationalLicences
{
    /*
    * to check if user has aithorized IP, use:
    * Swissbib\TargetsProxy\IPMatcher::isMatching($ipAddress, array $patterns = [])
    */

    /**
     * Build URL for access the content by authorized IPs
     *
     * @return String
     */
    protected function buildUrlForAuthorizedIPs()
    {

        /*
         * If I am in an authorized network, the link to the fulltext goes directly to the content. Example :
         * Article : ABI-Technik. Volume 27, Issue 3, Pages 160–168, ISSN (Online) 2191-4664, ISSN (Print) 0720-6763, DOI: 10.1515/ABITECH.2007.27.3.160, March 2011
         * URL : https://www.degruyter.com/openurl?genre=article&issn=2191-4664&volume=27&issue=3&spage=160
         */

        return "https://www.degruyter.com/openurl?genre=article&issn=2191-4664&volume=27&issue=3&spage=160";
    }

    /**
     * Build URL for access the content by unauthorized IPs (private users)
     *
     * @return String
     */
    protected function buildUrlForUnauthorizedIPs()
    {

        /*
        * If I am not in an authorized network, the url to access the content changes. It requires a Shibboleth authentication first.
        *
        * The url is the same as in Story B, but :
        * -needs to prepend https://www.degruyter.com/applib/openathens?entityID=https%3A%2F%2Feduid.ch%2Fidp%2Fshibboleth&openAthens2Redirect=
        * -needs to url-encode the url from Story B
        *
        * Result : https://www.degruyter.com/applib/openathens?entityID=https%3A%2F%2Feduid.ch%2Fidp%2Fshibboleth&openAthens2Redirect=https%3A%2F%2Fwww.degruyter.com%2Fopenurl%3Fgenre%3Darticle%26issn%3D2191-4664%26volume%3D27%26issue%3D3%26spage%3D160
        *
        * Note : this url doesn’t work right now, but it is possible to use unibas shibboleth endpoint instead :
        *
        * https://www.degruyter.com/applib/openathens?entityID=https%3A%2F%2Faai-logon.unibas.ch%2Fidp%2Fshibboleth&openAthens2Redirect=https%3A%2F%2Fwww.degruyter.com%2Fopenurl%3Fgenre%3Darticle%26issn%3D2191-4664%26volume%3D27%26issue%3D3%26spage%3D160
        */

        // example:
        return "https://www.degruyter.com/applib/openathens?entityID=https%3A%2F%2Faai-logon.unibas.ch%2Fidp%2Fshibboleth&openAthens2Redirect=https%3A%2F%2Fwww.degruyter.com%2Fopenurl%3Fgenre%3Darticle%26issn%3D2191-4664%26volume%3D27%26issue%3D3%26spage%3D160";
    }

}
