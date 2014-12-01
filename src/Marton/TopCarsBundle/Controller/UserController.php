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
use Marton\TopCarsBundle\Form\Type\UserDetailsType;
use Marton\TopCarsBundle\Repository\UserProgressRepository;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class UserController extends Controller{

    // Renders Leaderboard page
    public function leaderboardAction(){

        // Get all highscores
        /* @var $repository UserProgressRepository */
        $repository = $this->getDoctrine()->getRepository('MartonTopCarsBundle:UserProgress');
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
        $repository = $this->getDoctrine()->getRepository('MartonTopCarsBundle:UserProgress');
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

        // TODO: add validation
        if ($edit_form->isValid()) {

            $user_details = $edit_form->getData();
            $user->setDetails($user_details);
            $em->persist($user);
            $em->flush();

            return $this->redirect($this->generateUrl('marton_topcars_default'));
        }

        return $this->render('MartonTopCarsBundle:Default:Pages/account.html.twig', array(
            'details_form' => $edit_form->createView(),
            'user' => $user,
            'user_details' => $user_details
        ));
    }
} 