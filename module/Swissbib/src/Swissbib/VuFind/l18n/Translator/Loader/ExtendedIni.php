<?php
/**
 * VuFind Translate Adapter ExtendedIni
 *
 * PHP version 5
 *
 * Copyright (C) Villanova University 2010.
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
 * @category VuFind2
 * @package  Translator
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */

namespace Swissbib\VuFind\l18n\Translator\Loader;

use Zend\I18n\Exception\InvalidArgumentException,
    Zend\I18n\Translator\Loader\FileLoaderInterface,
    Zend\I18n\Translator\TextDomain,
    VuFind\I18n\Translator\Loader\ExtendedIni as VFExtendedIni;

/**
 * Handles the language loading and language file parsing
 *
 * @category VuFind2
 * @package  Translator
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */
class ExtendedIni extends VFExtendedIni
{

    /**
     * Constructor
     *
     * @param array  $pathStack      List of directories to search for language
     *                               files.
     * @param string $fallbackLocale Fallback locale to use for language strings
     *                               missing from selected file.
     */
    public function __construct($pathStack = array(), $fallbackLocale = null)
    {
        parent::__construct($pathStack, $fallbackLocale);
    }

    /**
     * Load(): defined by LoaderInterface.
     *
     * @param string $locale   Locale to read from language file
     * @param string $filename Language file to read (not used)
     *
     * @return TextDomain
     *
     * @throws InvalidArgumentException
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function load($locale, $filename)
    {
        return parent::load($locale, $filename);
    }
}
