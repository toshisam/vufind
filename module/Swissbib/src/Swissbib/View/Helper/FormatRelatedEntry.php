<?php
/**
 * Created by PhpStorm.
 * User: markusmaechler
 * Date: 03/07/15
 * Time: 09:38
 */

namespace Swissbib\View\Helper;

use Zend\I18n\Translator\TranslatorInterface;
use Zend\View\Helper\AbstractHelper;

class FormatRelatedEntry extends AbstractHelper {

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * Format relatedEntry element
     *
     * @param array $relatedEntry
     *
     * @return String
     */
    public function __invoke(array $relatedEntry)
    {
        $formattedEntry = isset($relatedEntry['name']) ? $relatedEntry['name'] : '';
        $formattedEntry .= isset($relatedEntry['secondName']) ? ', ' . $relatedEntry['secondName'] : '';
        $formattedEntry .= ' (' . $this->translator->translate($relatedEntry['relation']) . ')';

        return $formattedEntry;
    }
}