<?php
/**
 * Created by PhpStorm.
 * User: Marci
 * Date: 28/10/14
 * Time: 21:58
 */

namespace Marton\TopCarsBundle\Controller;


use Marton\TopCarsBundle\Form\Model\Registration;
use Marton\TopCarsBundle\Form\Type\RegistrationType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class AccountController extends Controller{

    public function registerAction()
    {
        $registration = new Registration();
        $form = $this->createForm(new RegistrationType(), $registration, array(
            'action' => $this->generateUrl('marton_topcars_create_account'),
        ));

        return $this->render(
            'MartonTopCarsBundle:Default:Pages/registration.html.twig',
            array('form' => $form->createView())
        );
    }


    public function createAction(Request $request)
    {
    }

} 