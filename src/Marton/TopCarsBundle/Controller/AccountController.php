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

    // Render the Registration page
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

    // Handles the submitted form to create an account
    public function createAction(Request $request){

        $form = $this->createForm(new RegistrationType(), new Registration());
        $form->handleRequest($request);

        if ($form->isValid()) {

            $registration = $form->getData();

            /* @var $user User */
            $user = $registration->getUser();

            $userProgress = new UserProgress();
            $user->setProgress($userProgress);
            $userDetails  = new UserDetails();
            $user->setDetails($userDetails);

            // Encode the password of the user
            $plainPassword = $user->getPassword();
            $encoderFactory = $this->container->get('security.encoder_factory');
            $encoder = $encoderFactory->getEncoder($user);
            $encodedPassword = $encoder->encodePassword($plainPassword, $user->getSalt());
            $user->setPassword($encodedPassword);

            $em = $this->getDoctrine()->getManager();

            // Assign the "Registered User" role to a new user
            $role = $em->getRepository('MartonTopCarsBundle:Role')->findOneBy(array('role' => 'ROLE_USER'));
            $user->addRole($role);

            $em->persist($user);
            $em->flush();

            // Automatically log in the user after a successful registration
            $token = new UsernamePasswordToken($user, null, 'secured_area', $user->getRoles());
            $this->get('security.context')->setToken($token);
            $this->get('session')->set('_security_main',serialize($token));

            // Set flash message that will appear only once
            $this->get('session')->getFlashBag()->add(
                'notice',
                'Welcome, now let the fun begin! :)'
            );

            return $this->redirect($this->generateUrl('marton_topcars_account'));
        }

        // If the validation failed, render the form again, this time with errors visible
        return $this->render(
            'MartonTopCarsBundle:Default:Pages/registration.html.twig',
            array('form' => $form->createView())
        );
    }

    // Render the Login page
    public function loginAction(Request $request){

        $session = $request->getSession();

        // Get the login error if there is one
        if ($request->attributes->has(SecurityContextInterface::AUTHENTICATION_ERROR)) {
            $error = $request->attributes->get(SecurityContextInterface::AUTHENTICATION_ERROR);
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

    // Handle the Ajax POST request to permanently delete an account
    public function deleteAccountAction(Request $request){

        /* @var $user User */
        $user = $this->get('security.context')->getToken()->getUser();

        $fileHelper = $this->get('file_helper');

        // Delete the user's profile picture
        if ($user->getDetails()->getProfilePicturePath() !== 'default.jpg'){

            $userProfilePicturePath = $user->getDetails()->getProfilePicturePath();
            $imagePath = $this->get('kernel')->getRootDir() . '/../web/bundles/martontopcars/images/avatar/' . $userProfilePicturePath;

            $fileHelper->removeFile($imagePath);
        }

        $suggested_cars = $user->getSuggestedCars();

        // Delete all the images the user has uploaded for her suggested cars
        foreach($suggested_cars as $car){

            if (($car->getImage() !== 'default.jpg') and ($car->getImage() !== 'default.png')){

                $imagePath = $this->get('kernel')->getRootDir() . '/../web/bundles/martontopcars/images/card_game_suggest/'.$car->getImage();
                $fileHelper->removeFile($imagePath);
            }
        }

        // Log the user out
        $this->get('security.context')->setToken(null);
        $this->get('request')->getSession()->invalidate();

        // Remove the user from the database
        $em = $this->getDoctrine()->getManager();
        $em->remove($user);
        $em->flush();

        $response = new Response(json_encode(array(
            'error' => '')));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    // Renders the Account page
    public function accountAction(){

        /* @var $user User */
        $user= $this->get('security.context')->getToken()->getUser();

        /* @var $userDetails UserDetails */
        $userDetails = $user->getDetails();

        // Create Form for editing user details
        $editForm = $this->createForm(new UserDetailsType(), $userDetails, array(
            'action' => $this->generateUrl('marton_topcars_account_update'),
        ));

        return $this->render('MartonTopCarsBundle:Default:Pages/account.html.twig', array(
            'details_form' => $editForm->createView(),
            'user' => $user,
            'user_details' => $userDetails
        ));
    }

    // Handles the submitted form to update user details
    public function updateAccountAction(Request $request){      

        /* @var $user User */
        $user = $this->get('security.context')->getToken()->getUser();

        /* @var $userDetails UserDetails */
        $userDetails = $user->getDetails();

        $editForm = $this->createForm(new UserDetailsType(), $userDetails);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {

            $newUserDetails = $editForm->getData();

            $imageFile = $newUserDetails->getImageFile();

            // Check if the user has actually uploaded an image
            if($imageFile != null){

                $fileHelper = $this->get('file_helper');
                
                $avatarDirPath = $this->get('kernel')->getRootDir() . '/../web/bundles/martontopcars/images/avatar/';

                // Remove the user's previous profile picture
                if ($userDetails->getProfilePicturePath() !== 'default.jpg'){

                    $oldPath = $avatarDirPath . $userDetails->getProfilePicturePath();
                    $fileHelper->removeFile($oldPath);
                }

                // Renaming the image to avoid clash between this and other images
                $newFileName = $fileHelper->makeUniqueName($user->getId(), $imageFile->getClientOriginalName());
                $newUserDetails->setProfilePicturePath($newFileName);

                // Moving the image to the "avatar" directory
                $imageFile->move($avatarDirPath, $newFileName);
            }

            $imageFile = null;

            $user->setDetails($newUserDetails);

            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $em->flush();

            // Set flash message that will appear only once
            $this->get('session')->getFlashBag()->add(
                'notice',
                'Your changes have been successfully saved! :)'
            );
        }

        return $this->render('MartonTopCarsBundle:Default:Pages/account.html.twig', array(
            'details_form' => $editForm->createView(),
            'user' => $user,
            'user_details' => $userDetails
        ));
    }
}