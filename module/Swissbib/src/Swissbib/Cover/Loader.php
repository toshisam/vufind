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
     * Load an image given an ISBN and/or content type.
     *
     * @param string $isbn       ISBN
     * @param string $size       Requested size
     * @param string $type       Content type
     * @param string $title      Title of book (for dynamic covers)
     * @param string $author     Author of the book (for dynamic covers)
     * @param string $callnumber Callnumber (unique id for dynamic covers)
     * @param string $issn       ISSN
     * @param string $oclc       OCLC number
     * @param string $upc        UPC number
     *
     * @return void
     */
    public function loadImage($isbn = null, $size = 'small', $type = null,
        $title = null, $author = null, $callnumber = null,
        $issn = null, $oclc = null, $upc = null
    ) {
        // Sanitize parameters:
        $this->isbn = new ISBN($isbn);
        $this->issn = empty($issn)
            ? null
            : substr(preg_replace('/[^0-9X]/', '', strtoupper($issn)), 0, 8);
        $this->oclc = $oclc;
        $this->upc = $upc;
        $this->type = $type;
        $this->size = $size;

        // Display a fail image unless our parameters pass inspection and we
        // are able to display an ISBN or content-type-based image.
        if (!in_array($this->size, $this->validSizes)) {
            $this->loadUnavailable();
        } else if (!$this->fetchFromAPI()
            && !$this->fetchFromContentType()
        ) {
            if (isset($this->config->Content->makeDynamicCovers)
                && false !== $this->config->Content->makeDynamicCovers
            ) {
                $this->image = $this->getCoverGenerator()
                    ->generate($title, $author, $callnumber);
                $this->contentType = 'image/png';
            } else {
                $this->loadUnavailable();
            }
        }
    }

}
