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
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

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
        $em = $this->getDoctrine()->getManager();

        $form = $this->createForm(new RegistrationType(), new Registration());

        $form->handleRequest($request);

        if ($form->isValid()) {

            $role = $em->getRepository('MartonTopCarsBundle:Role')->findOneBy(array('role' => 'ROLE_USER'));

            $registration = $form->getData();

            $user = $registration->getUser();

            // Encode the password of the user
            $plainPassword = $user->getPassword();

            $encoderFactory = $this->container->get('security.encoder_factory');
            $encoder = $encoderFactory->getEncoder($user);

            $encodedPassword = $encoder->encodePassword($plainPassword, $user->getSalt());
            $user->setPassword($encodedPassword);

            $user->addRole($role);

            $em->persist($user);
            $em->flush();

            // Automatically log in the user after successful registration
            $token = new UsernamePasswordToken($user, null, 'secured_area', $user->getRoles());
            $this->get('security.context')->setToken($token);
            $this->get('session')->set('_security_main',serialize($token));

            $this->get('session')->getFlashBag()->add(
                'notice',
                'Welcome, now let the fun begin! :)'
            );

            return $this->redirect($this->generateUrl('marton_topcars_default'));
        }

        return $this->render(
            'MartonTopCarsBundle:Default:Pages/registration.html.twig',
            array('form' => $form->createView())
        );
    }

} 