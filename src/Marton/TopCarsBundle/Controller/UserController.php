<?php
/**
 * Created by PhpStorm.
 * User: Marci
 * Date: 08/11/14
 * Time: 16:15
 */

namespace Marton\TopCarsBundle\Controller;


use Marton\TopCarsBundle\Classes\StatisticsCalculator;
use Marton\TopCarsBundle\Entity\User;
use Marton\TopCarsBundle\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class UserController extends Controller{

    // Renders Leaderboard page
    public function leaderboardAction(){

        // Get all highscores
        /* @var $repository UserRepository */
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
        /* @var $repository UserRepository */
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
} 