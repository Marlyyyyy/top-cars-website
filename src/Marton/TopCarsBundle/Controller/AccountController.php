<?php
/**
 * Created by PhpStorm.
 * User: Marci
 * Date: 28/10/14
 * Time: 21:58
 */

namespace Marton\TopCarsBundle\Controller;


use Marton\TopCarsBundle\Entity\UserProgress;
use Marton\TopCarsBundle\Entity\UserDetails;
use Marton\TopCarsBundle\Classes\FileHelper;
use Marton\TopCarsBundle\Form\Model\Registration;
use Marton\TopCarsBundle\Form\Type\RegistrationType;
use Marton\TopCarsBundle\Form\Type\UserDetailsType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\SecurityContextInterface;

class AccountController extends Controller{

    // Render Registration page
    public function registerAction(){
        $registration = new Registration();
        $form = $this->createForm(new RegistrationType(), $registration, array(
            'action' => $this->generateUrl('marton_topcars_create_account'),
        ));

        return $this->render(
            'MartonTopCarsBundle:Default:Pages/registration.html.twig',
            array('form' => $form->createView())
        );
    }

    // Create an account
    public function createAction(Request $request){

        $em = $this->getDoctrine()->getManager();

        $form = $this->createForm(new RegistrationType(), new Registration());

        $form->handleRequest($request);

        if ($form->isValid()) {

            $role = $em->getRepository('MartonTopCarsBundle:Role')->findOneBy(array('role' => 'ROLE_USER'));
            $user_progress = new UserProgress();
            $user_details = new UserDetails();

            $registration = $form->getData();

            $user = $registration->getUser();

            // Encode the password of the user
            $plainPassword = $user->getPassword();

            $encoderFactory = $this->container->get('security.encoder_factory');
            $encoder = $encoderFactory->getEncoder($user);

            $encodedPassword = $encoder->encodePassword($plainPassword, $user->getSalt());
            $user->setPassword($encodedPassword);

            $user->addRole($role);
            $user->setProgress($user_progress);
            $user->setDetails($user_details);

            $em->persist($user_progress);
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

    // Render Login page (even after unsuccessful login attemp)
    public function loginAction(Request $request){

        $session = $request->getSession();

        // get the login error if there is one
        if ($request->attributes->has(SecurityContextInterface::AUTHENTICATION_ERROR)) {
            $error = $request->attributes->get(
                SecurityContextInterface::AUTHENTICATION_ERROR
            );
        } elseif (null !== $session && $session->has(SecurityContextInterface::AUTHENTICATION_ERROR)) {
            $error = $session->get(SecurityContextInterface::AUTHENTICATION_ERROR);
            $session->remove(SecurityContextInterface::AUTHENTICATION_ERROR);
        } else {
            $error = '';
        }

        // Last username entered by the user
        $lastUsername = (null === $session) ? '' : $session->get(SecurityContextInterface::LAST_USERNAME);

        return $this->render(
            'MartonTopCarsBundle:Default:Pages/login.html.twig',
            array(
                'last_username' => $lastUsername,
                'error'         => $error,
            )
        );
    }

    // Ajax request to delete an account permanently
    public function deleteAccountAction(Request $request){

        // Get entity manager
        $em = $this->getDoctrine()->getManager();

        // Get user entity
        /* @var $user User */
        $user= $this->get('security.context')->getToken()->getUser();

        $file_helper = new FileHelper();

        // Delete the user's profile picture
        if ($user->getDetails()->getProfilePicturePath() !== 'default.jpg'){

            $image_path = $this->get('kernel')->getRootDir() . '/../web/bundles/martontopcars/images/avatar/'.$user->getDetails()->getProfilePicturePath();

            $file_helper->removeFile($image_path);
        }

        $suggested_cars = $user->getSuggestedCars();

        // Delete all the images the user has uploaded for her suggested cars
        foreach($suggested_cars as $car){

            if ($car->getImage() !== 'default.jpg'){

                $image_path = $this->get('kernel')->getRootDir() . '/../web/bundles/martontopcars/images/card_game_suggest/'.$car->getImage();

                $file_helper->removeFile($image_path);
            }
        }

        // Log the user out
        $this->get('security.context')->setToken(null);
        $this->get('request')->getSession()->invalidate();

        // Remove the user from the database
        $em->remove($user);
        $em->flush();

        $response = new Response(json_encode(array(
            'error' => '')));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    // Renders Account page
    public function accountAction(){

        // Get entity manager
        $em = $this->getDoctrine()->getManager();

        // Get user entity
        /* @var $user User */
        $user= $this->get('security.context')->getToken()->getUser();

        // Get details of the user
        /* @var $user_details UserDetails */
        $user_details = $user->getDetails();

        // Create Form for editing user details
        $edit_form = $this->createForm(new UserDetailsType(), $user_details, array(
            'action' => $this->generateUrl('marton_topcars_account_update'),
        ));

        return $this->render('MartonTopCarsBundle:Default:Pages/account.html.twig', array(
            'details_form' => $edit_form->createView(),
            'user' => $user,
            'user_details' => $user_details
        ));
    }

    // Handles the submitted form to update user details
    public function updateAccountAction(Request $request){

        // Get entity manager
        $em = $this->getDoctrine()->getManager();

        // Get user entity
        /* @var $user User */
        $user= $this->get('security.context')->getToken()->getUser();

        // Get details of the user
        /* @var $user_details UserDetails */
        $user_details = $user->getDetails();

        $edit_form = $this->createForm(new UserDetailsType(), $user_details);

        $edit_form->handleRequest($request);

        if ($edit_form->isValid()) {

            $new_user_details = $edit_form->getData();

            $image_file = $new_user_details->getImageFile();

            // Check if the user has actually uploaded an image
            if($image_file != null){

                $file_helper = new FileHelper();

                $avatar_dir_path = $this->get('kernel')->getRootDir() . '/../web/bundles/martontopcars/images/avatar/';

                // Remove previous image
                if ($user_details->getProfilePicturePath() !== 'default.jpg'){

                    $old_path = $avatar_dir_path.$user_details->getProfilePicturePath();
                    $file_helper->removeFile($old_path);
                }

                // Renaming the image to avoid user clash
                $new_file_name = $file_helper->makeUniqueName($user->getId(), $image_file->getClientOriginalName());
                $new_user_details->setProfilePicturePath($new_file_name);

                // Moving the image to the "avatar" directory
                $image_file->move($avatar_dir_path, $new_file_name);
            }

            $image_file = null;

            $user->setDetails($new_user_details);
            $em->persist($user);
            $em->flush();

            // Set flash message
            $this->get('session')->getFlashBag()->add(
                'notice',
                'Your changes have been successfully saved! :)'
            );
        }

        return $this->render('MartonTopCarsBundle:Default:Pages/account.html.twig', array(
            'details_form' => $edit_form->createView(),
            'user' => $user,
            'user_details' => $user_details
        ));
    }
} 