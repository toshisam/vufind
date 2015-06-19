<?php

namespace Swissbib\Record\Form;

use Zend\Form\Annotation;

/**
 * @Annotation\Name("copy")
 * @Annotation\Hydrator("Zend\Stdlib\Hydrator\ObjectProperty")
 */
class CopyForm
{
    /**
     * @Annotation\Name("pickup-location")
     * @Annotation\Options({"label":"pick_up_location"})
     * @Annotation\Type("Zend\Form\Element\Select")
     */
    public $pickup_location;

    /**
     * @Annotation\Name("sub-author")
     * @Annotation\Attributes({"type":"text"})
     * @Annotation\Options({"label":"Author"})
     * @Annotation\AllowEmpty(true)
     * @Annotation\Filter({"name":"StringTrim"})
     * @Annotation\Filter({"name":"StripTags"})
     * @Annotation\Validator({"name":"StringLength", "options":{"min":0, "max":50}})
     */
    public $sub_author;

    /**
     * @Annotation\Name("sub-title")
     * @Annotation\Attributes({"type":"text"})
     * @Annotation\Options({"label":"Title"})
     * @Annotation\AllowEmpty(true)
     * @Annotation\Filter({"name":"StringTrim"})
     * @Annotation\Filter({"name":"StripTags"})
     * @Annotation\Validator({"name":"StringLength", "options":{"min":0, "max":100}})
     */
    public $sub_title;

    /**
     * @Annotation\Name("pages")
     * @Annotation\Attributes({"type":"text"})
     * @Annotation\Options({"label":"pages"})
     * @Annotation\Filter({"name":"StringTrim"})
     * @Annotation\Filter({"name":"StripTags"})
     * @Annotation\Validator({"name":"StringLength", "options":{"min":0, "max":30}})
     */
    public $pages;

    /**
     * @Annotation\Name("note1")
     * @Annotation\Attributes({"type":"text"})
     * @Annotation\Options({"label":"note1"})
     * @Annotation\AllowEmpty(true)
     * @Annotation\Filter({"name":"StringTrim"})
     * @Annotation\Filter({"name":"StripTags"})
     * @Annotation\Validator({"name":"StringLength", "options":{"min":0, "max":50}})
     */
    public $note1;

    /**
     * @Annotation\Name("note2")
     * @Annotation\Attributes({"type":"textarea"})
     * @Annotation\Options({"label":"kommentar"})
     * @Annotation\AllowEmpty(true)
     * @Annotation\Filter({"name":"StringTrim"})
     * @Annotation\Filter({"name":"StripTags"})
     * @Annotation\Validator({"name":"StringLength", "options":{"min":0, "max":50}})
     */
    public $note2;
}