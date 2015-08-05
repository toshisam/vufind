<?php
namespace Swissbib\View\Helper;

use VuFind\View\Helper\Root\RecordLink as VfRecordLink;

/**
 * Build record links
 * Override related method to support ctrlnum type
 *
 */
class RecordLink extends VfRecordLink
{

    /**
     * @inheritDoc
     */
    public function related($link, $escape = true)
    {
        if ($link['type'] === 'ctrlnum') {
            return $this->buildCtrlNumRelatedLink($link, $escape);
        } else {
            return parent::related($link, $escape);
        }
    }

    /**
     * Build link for ctrlnum
     *
     * @param      $link
     * @param bool $escape
     * @return string
     */
    protected function buildCtrlNumRelatedLink($link, $escape = true)
    {
        $urlHelper    = $this->getView()->plugin('url');
        $escapeHelper = $this->getView()->plugin('escapeHtml');

        $url = $urlHelper('search-results')
                . '?lookfor=' . urlencode($link['value'])
                . '&type=ctrlnum&jumpto=1';

        return $escape ? $escapeHelper($url) : $url;
    }

    /**
     * @param array $item
     * @param string $recordId
     *
     * @return string
     */
    public function getCopyUrl(array $item, $recordId)
    {
        if (!isset($item['adm_code']) || !isset($item['localid']) || !isset($item['sequencenumber'])) return $item['userActions']['photoRequestLink'];

        $urlHelper    = $this->getView()->plugin('url');
        $escapeHelper = $this->getView()->plugin('escapeHtml');
        $bibRecordId = $item['bib_library'] . '-' . $item['bibsysnumber'];
        $itemId = $item['adm_code'] . $item['localid'] . $item['sequencenumber'];

        $url = $urlHelper('record-copy', ['id' => $recordId]) . '?recordId=' . $bibRecordId . '&itemId=' . $itemId;

        return $escapeHelper($url);
    }
}
