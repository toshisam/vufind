<?php

namespace Swissbib\MyResearch\Form;

use Zend\Form\Annotation;

/**
 * @Annotation\Name("address")
 * @Annotation\Hydrator("Zend\Stdlib\Hydrator\ObjectProperty")
 */
class AddressForm
{
    /**
     * @Annotation\Name("z304-address-1")
     * @Annotation\Options({"label":"Address"})
     * @Annotation\Attributes({"type":"text"})
     */
    public $z304_address_1;

    /**
     * @Annotation\Name("z304-address-2")
     * @Annotation\Attributes({"type":"text"})
     * @Annotation\Filter({"name":"StringTrim"})
     * @Annotation\Filter({"name":"StripTags"})
     * @Annotation\Validator({"name":"StringLength", "options":{"min":0, "max":50}})
     * @Annotation\Validator({"name":"Regex", "options":{"pattern":"/^[\w\s\d.\/(),-]*$/u"}})
     * @Annotation\ErrorMessage("input_too_long_or_disallowed")
     */
    public $z304_address_2;

    /**
     * @Annotation\Name("z304-address-3")
     * @Annotation\Attributes({"type":"text"})
     * @Annotation\AllowEmpty(true)
     * @Annotation\Filter({"name":"StringTrim"})
     * @Annotation\Filter({"name":"StripTags"})
     * @Annotation\Validator({"name":"StringLength", "options":{"min":0, "max":50}})
     * @Annotation\Validator({"name":"Regex", "options":{"pattern":"/^[\w\s\d.\/(),-]*$/u"}})
     * @Annotation\ErrorMessage("input_too_long_or_disallowed")
     */
    public $z304_address_3;

    /**
     * @Annotation\Name("z304-address-4")
     * @Annotation\Attributes({"type":"text"})
     * @Annotation\AllowEmpty(true)
     * @Annotation\Filter({"name":"StringTrim"})
     * @Annotation\Filter({"name":"StripTags"})
     * @Annotation\Validator({"name":"StringLength", "options":{"min":0, "max":50}})
     * @Annotation\Validator({"name":"Regex", "options":{"pattern":"/^[\w\s\d.\/(),-]*$/u"}})
     * @Annotation\ErrorMessage("input_too_long_or_disallowed")
     */
    public $z304_address_4;

    /**
     * @Annotation\Name("z304-address-5")
     * @Annotation\Attributes({"type":"text"})
     * @Annotation\AllowEmpty(true)
     * @Annotation\Filter({"name":"StringTrim"})
     * @Annotation\Filter({"name":"StripTags"})
     * @Annotation\Validator({"name":"StringLength", "options":{"min":0, "max":50}})
     * @Annotation\Validator({"name":"Regex", "options":{"pattern":"/^[\w\s\d.\/(),-]*$/u"}})
     * @Annotation\ErrorMessage("input_too_long_or_disallowed")
     */
    public $z304_address_5;

    /**
     * @Annotation\Name("z304-email-address")
     * @Annotation\Options({"label":"Email"})
     * @Annotation\AllowEmpty(true)
     * @Annotation\Type("Zend\Form\Element\Email")
     * @Annotation\Validator({"name":"StringLength", "options":{"min":0, "max":60}})
     * @Annotation\Validator({"name":"EmailAddress", "options":{"min":0, "max":60}})
     * @Annotation\ErrorMessage("Email address is invalid")
     */
    public $z304_email_address;

    /**
     * @Annotation\Name("z304-telephone-1")
     * @Annotation\Options({"label":"Phone Number 1"})
     * @Annotation\Attributes({"type":"tel"})
     * @Annotation\Filter({"name":"StringTrim"})
     * @Annotation\Validator({"name":"StringLength", "options":{"min":0, "max":30}})
     * @Annotation\Validator({"name":"Regex", "options":{"pattern":"/^[\w\s\d(),\/+.-]*$/u"}})
     * @Annotation\ErrorMessage("input_too_long_or_disallowed")
     */
    public $z304_telephone_1;

    /**
     * @Annotation\Name("z304-telephone-2")
     * @Annotation\Options({"label":"Phone Number 2"})
     * @Annotation\Attributes({"type":"tel"})
     * @Annotation\AllowEmpty(true)
     * @Annotation\Filter({"name":"StringTrim"})
     * @Annotation\Validator({"name":"StringLength", "options":{"min":0, "max":30}})
     * @Annotation\Validator({"name":"Regex", "options":{"pattern":"/^[\w\s\d(),\/+.-]*$/u"}})
     * @Annotation\ErrorMessage("input_too_long_or_disallowed")
     */
    public $z304_telephone_2;

    /**
     * @Annotation\Name("z304-telephone-3")
     * @Annotation\Options({"label":"Phone Number 3"})
     * @Annotation\Attributes({"type":"tel"})
     * @Annotation\AllowEmpty(true)
     * @Annotation\Filter({"name":"StringTrim"})
     * @Annotation\Validator({"name":"StringLength", "options":{"min":0, "max":30}})
     * @Annotation\Validator({"name":"Regex", "options":{"pattern":"/^[\w\s\d(),\/+.-]*$/u"}})
     * @Annotation\ErrorMessage("input_too_long_or_disallowed")
     */
    public $z304_telephone_3;

    /**
     * @Annotation\Name("z304-telephone-4")
     * @Annotation\Options({"label":"Phone Number 4"})
     * @Annotation\Attributes({"type":"tel"})
     * @Annotation\AllowEmpty(true)
     * @Annotation\Filter({"name":"StringTrim"})
     * @Annotation\Validator({"name":"StringLength", "options":{"min":0, "max":30}})
     * @Annotation\Validator({"name":"Regex", "options":{"pattern":"/^[\w\s\d(),\/+.-]*$/u"}})
     * @Annotation\ErrorMessage("input_too_long_or_disallowed")
     */
    public $z304_telephone_4;
}