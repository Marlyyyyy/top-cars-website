<?php
/**
 * Created by PhpStorm.
 * User: Marci
 * Date: 28/10/14
 * Time: 21:54
 */

namespace Marton\TopCarsBundle\Form\Type;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class RegistrationType extends AbstractType
{
    // Build the Registration form containg fields for a User entity as well as a Terms checkbox
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('user', new UserType(), array('label' => false));
        $builder->add('terms','checkbox',array('property_path' => 'termsAccepted', 'label' => 'Terms:'));
        $builder->add('register', 'submit');
    }

    public function getName()
    {
        return 'registration';
    }
}