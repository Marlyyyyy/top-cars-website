<?php
/**
 * Created by PhpStorm.
 * User: Marci
 * Date: 08/11/14
 * Time: 16:15
 */

namespace Marton\TopCarsBundle\Controller;


use Marton\TopCarsBundle\Classes\AchievementCalculator;
use Marton\TopCarsBundle\Classes\PriceCalculator;
use Marton\TopCarsBundle\Classes\StatisticsCalculator;
use Marton\TopCarsBundle\Entity\User;
use Marton\TopCarsBundle\Entity\UserDetails;
use Marton\TopCarsBundle\Entity\UserProgress;
use Marton\TopCarsBundle\Classes\FileHelper;
use Marton\TopCarsBundle\Form\Type\UserDetailsType;
use Marton\TopCarsBundle\Repository\UserProgressRepository;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\File;

class UserController extends Controller{

    // Renders Leaderboard page
    public function leaderboardAction(){

        // Get all highscores
        /* @var $repository UserProgressRepository */
        $repository = $this->getDoctrine()->getRepository('MartonTopCarsBundle:User');
        $users = $repository-> findHighscores();

        foreach ($users as $user){
            $statisticsCalculator = new StatisticsCalculator($user);
            /* @var $user User */
            $user->setStatistics($statisticsCalculator->getStatistics());
        }

        return $this->render('MartonTopCarsBundle:Default:Pages/leaderboard.html.twig', array(
            'users' => $users
        ));
    }

    // Renders User profile page
    public function profileAction($user){

        // Get details of this user
        /* @var $repository UserProgressRepository */
        $repository = $this->getDoctrine()->getRepository('MartonTopCarsBundle:User');
        $user_details = $repository-> findDetailsOfUser($user);

        $statisticsCalculator = new StatisticsCalculator($user_details[0]);
        $user_details[0]->setStatistics($statisticsCalculator->getStatistics());

        $cars = $user_details[0]->getCars();
        $cars_value = 0;
        foreach($cars as $car){
            $cars_value += $car->getPrice();
        }

        return $this->render('MartonTopCarsBundle:Default:Pages/user.html.twig', array(
            'user' => $user_details[0],
            'cars' => $cars,
            'cars_value' => $cars_value
        ));
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
} 