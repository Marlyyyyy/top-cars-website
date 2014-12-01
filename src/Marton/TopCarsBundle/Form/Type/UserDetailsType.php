<?php
/**
 * Created by PhpStorm.
 * User: Marci
 * Date: 01/12/14
 * Time: 12:18
 */

namespace Marton\TopCarsBundle\Form\Type;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class UserDetailsType extends AbstractType{

    public function buildForm(FormBuilderInterface $builder, array $options){

        $builder
            ->add('firstName', 'text', array('required'=>false, 'label' => 'First Name'))
            ->add('lastName', 'text', array('required'=>false, 'label' => 'Last Name'))
            ->add('profilePicturePath', 'email', array('required'=>false, 'label' => 'Avatar', 'empty_data' => 'default.jpg'))
            ->add('country', 'text', array('required'=>false))
            ->add('about', 'textarea', array('required'=>false))
            ->add('save', 'submit', array('label' => 'Save'));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver){

        $resolver->setDefaults(array(
            'data_class' => 'Marton\TopCarsBundle\Entity\UserDetails'
        ));
    }

    /**
     * Returns the name of this type.
     *
     * @return string The name of this type
     */
    public function getName()
    {
        return "userDetails";
    }
}