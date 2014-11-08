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
            ->add('model', 'text')
            ->add('image', 'file')
            ->add('speed', 'text')
            ->add('power', 'text')
            ->add('torque', 'text')
            ->add('acceleration', 'text')
            ->add('weight', 'text')
            ->add('comment', 'text')
            ->add('save', 'submit', array('label' => 'Submit'));
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