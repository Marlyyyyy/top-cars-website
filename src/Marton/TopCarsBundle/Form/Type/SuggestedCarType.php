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
            ->add('model', 'text', array('invalid_message' => 'The name of the model has to be text!'), array('error_bubbling' => true))
            ->add('image_file', 'file', array('required'=>false, 'invalid_message' => 'The image cannot be greater than 1MB!'), array('error_bubbling' => true))
            ->add('speed', 'number', array('invalid_message' => 'Speed has to be a number!'), array('error_bubbling' => true))
            ->add('power', 'number', array('invalid_message' => 'Power has to be a number!'), array('error_bubbling' => true))
            ->add('torque', 'number', array('invalid_message' => 'Torque has to be a number!'), array('invalid_message' => 'The name of the model has to be text!'), array('error_bubbling' => true))
            ->add('acceleration', 'number', array('invalid_message' => 'Acceleration has to be a number!'), array('error_bubbling' => true))
            ->add('weight', 'number', array('invalid_message' => 'Weight has to be a number!'), array('error_bubbling' => true))
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