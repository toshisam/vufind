<?php

namespace Swissbib\Record;

use Zend\Form\Annotation\AnnotationBuilder;
use Zend\Form\Element\Csrf;
use Zend\ServiceManager\ServiceManager;
use Zend\Form\Form;

class Factory
{
    /**
     * @param ServiceManager $sm
     *
     * @return Form
     */
    public static function getCopyForm(ServiceManager $sm)
    {
        $builder = new AnnotationBuilder();
        $form = $builder->createForm('\\Swissbib\\Record\\Form\\CopyForm');
        $form->add(new Csrf('security'));
        $form->add([
            'name' => 'submit',
            'type'  => 'Submit',
            'attributes' => [
                'value' => 'Order',
            ],
        ]);

        return $form;
    }
}