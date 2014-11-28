<?php
/**
 * Created by PhpStorm.
 * User: Marci
 * Date: 28/10/14
 * Time: 21:46
 */

namespace Marton\TopCarsBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class UserType extends AbstractType {

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('username', 'text', array('label' => 'Username:'));
        $builder->add('email', 'email', array('label' => 'E-mail address:'));
        $builder->add('password', 'repeated', array(
            'first_name'    => 'password',
            'first_options' => array('label' => 'Password:'),
            'second_name'   => 'confirm',
            'second_options' => array('label' => 'Confirm password:'),
            'type'          => 'password',
            'invalid_message' => 'The password fields must match.',
        ));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Marton\TopCarsBundle\Entity\User'
        ));
    }

    /**
     * Returns the name of this type.
     *
     * @return string The name of this type
     */
    public function getName()
    {
        return 'user';
    }
}