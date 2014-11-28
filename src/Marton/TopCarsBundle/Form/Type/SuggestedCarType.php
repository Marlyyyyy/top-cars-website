<?php
/**
 * Created by PhpStorm.
 * User: Marci
 * Date: 08/11/14
 * Time: 14:10
 */

namespace Marton\TopCarsBundle\Form\Type;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class SuggestedCarType extends AbstractType{

    public function buildForm(FormBuilderInterface $builder, array $options){

        $builder
            ->add('model', 'text', array('error_bubbling' => true))
            ->add('image_file', 'file', array('required'=>false), array('error_bubbling' => true))
            ->add('speed', 'number', array('error_bubbling' => true))
            ->add('power', 'number', array('error_bubbling' => true))
            ->add('torque', 'number', array('error_bubbling' => true))
            ->add('acceleration', 'number', array('error_bubbling' => true))
            ->add('weight', 'number', array('error_bubbling' => true))
            ->add('comment', 'textarea', array('error_bubbling' => true))
            ->add('save', 'submit', array('label' => 'Submit'), array('error_bubbling' => true));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver){

        $resolver->setDefaults(array(
            'data_class' => 'Marton\TopCarsBundle\Entity\SuggestedCar'
        ));
    }

    /**
     * Returns the name of this type.
     *
     * @return string The name of this type
     */
    public function getName()
    {
        return "suggestedCar";
    }
}