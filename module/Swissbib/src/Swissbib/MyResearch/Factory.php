<?php

namespace Swissbib\MyResearch;
use Zend\Form\Annotation\AnnotationBuilder;
use Zend\Form\Element\Csrf;
use Zend\ServiceManager\ServiceManager;
use Zend\Form\Form;
use Zend\Validator\AbstractValidator;

class Factory
{
    /**
     * @param ServiceManager $sm
     *
     * @return Form
     */
    public static function getAddressForm(ServiceManager $sm)
    {
        AbstractValidator::setDefaultTranslator($sm->get('\\VuFind\\Translator'));

        $builder = new AnnotationBuilder();
        $form = $builder->createForm('\\Swissbib\\MyResearch\\Form\\AddressForm');
        $form->add(new Csrf('security'));
        $form->add(
            [
            'name' => 'submit',
            'type'  => 'Submit',
            'attributes' => [
                'value' => 'Save',
            ],
            ]
        );

        return $form;
    }
}