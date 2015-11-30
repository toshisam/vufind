<?php
/**
 * Base class for loading images (shared by Cover\Loader and QRCode\Loader)
 *
 * PHP version 5
 *
 * Copyright (C) Villanova University 2007.
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
 * @package  Cover_Generator
 * @author   Matthias Edel <matthias.edel@unibas.ch>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/use_of_external_content Wiki
 */
namespace Swissbib\Cover;

use VuFind\Cover\Loader as VFLoader;
use VuFindCode\ISBN, VuFind\Content\Covers\PluginManager as ApiManager;

/**
 * Base class for loading images (shared by Cover\Loader and QRCode\Loader)
 *
 * @category VuFind2
 * @package  Cover_Generator
 * @author   Matthias Edel <matthias.edel@unibas.ch>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/use_of_external_content Wiki
 */
class Loader extends VFLoader
{

    /**
     * Array containing map of allowed file extensions to mimetypes
     * (to be extended)
     *
     * @var array
     */
    protected $allowedFileExtensions = [
        "gif" => "image/gif",
        "jpeg" => "image/jpeg", "jpg" => "image/jpeg",
        "png" => "image/png",
        "tiff" => "image/tiff", "tif" => "image/tiff",
        "svg" => "image/svg+xml"
    ];


    /**
     * Media-Type Config
     *
     * @var \Zend\Config\Config
     */
    protected $mediatypesIconFiles;



    /**
     * Loads a MediaTypesIcon.
     * Load the user-specified "cover unavailable" graphic (or default if none
     * specified).
     *
     * @return void
     * @author Matthias Edel <matthias.edel@unibas.ch>
     */
    public function loadUnavailable()
    {
        $format = $_GET['format'];
        $mediaType = $this->mediatypesIconFiles->MediatypesIconsFiles->$format;

        if (!(null == $mediaType)) {
            // try loading a mediatype-icon:
            $file = $this->searchTheme('images/mediaicons/' . $mediaType . '.svg');
            if (!file_exists($file)) {
                throw new \Exception('Could not load default fail image.');
            }
            $this->contentType = $this->getContentTypeFromExtension($file);
            $this->image = file_get_contents($file);
        } else {
            // if no mediatypeicon found, call parent, which loads notavailable-image:
            parent::loadUnavailable();
        }

    }

    /**
     * Constructor
     *
     * @param \Zend\Config\Config    $config                VuFind configuration
     * @param ApiManager             $manager               Plugin manager for API handlers
     * @param \VuFindTheme\ThemeInfo $theme                 VuFind theme tools
     * @param \Zend\Http\Client      $client                HTTP client
     * @param string                 $baseDir               Directory to store downloaded images
     * (set to system temp dir if not otherwise specified)
     * @param \Zend\Config\Config    $$mediatypesIconsFiles Filenames for MediaTypeIcons
     */
    public function __construct($config, ApiManager $manager,
    \VuFindTheme\ThemeInfo $theme, \Zend\Http\Client $client, $baseDir = null,
        \Zend\Config\Config $mediatypesIconFiles
    ) {
        parent::__construct($config, $manager, $theme, $client, $baseDir);

        $this->mediatypesIconFiles = $mediatypesIconFiles;

    }


}
