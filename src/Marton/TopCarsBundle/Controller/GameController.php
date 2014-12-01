<?php
/**
 * Created by PhpStorm.
 * User: Marci
 * Date: 01/12/14
 * Time: 10:58
 */

namespace Marton\TopCarsBundle\Controller;


use Marton\TopCarsBundle\Classes\AchievementCalculator;
use Marton\TopCarsBundle\Entity\User;
use Marton\TopCarsBundle\Entity\UserProgress;
use Marton\TopCarsBundle\Repository\CarRepository;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class GameController extends Controller{

    public function gameAction(){

        // Get entity manager
        $em = $this->getDoctrine()->getManager();

        // Get user entity
        /* @var $user User */
        $user= $this->get('security.context')->getToken()->getUser();

        // Get the progress
        /* @var $user User */
        /* @var $progress UserProgress */
        $progress = $user->getProgress();
        $user_score = $progress->getScore();

        // Get all cars
        /* @var $repository CarRepository */
        $repository = $this->getDoctrine()->getRepository('MartonTopCarsBundle:Car');
        $cars = $repository-> findAllCarsAsArray();

        // Get cars owned and selected by the user
        $selected_cars = $em->getRepository('MartonTopCarsBundle:Car')->findSelectedCarsOfUser($user->getId());

        // To calculate score initially
        $achievemetCalculator = new AchievementCalculator();
        $user_level_info = $achievemetCalculator->calculateLevel($user_score);
        $user_level_info["score"] = $user_score;

        // Test
        //$achievemetCalculator->printAllLevelScore();
        //$achievemetCalculator->printLevel();

        return $this->render('MartonTopCarsBundle:Default:Pages/game.html.twig', array(
            "deck" => json_encode($cars),
            "user_deck" => json_encode($selected_cars),
            "user_level_info" => json_encode($user_level_info)
        ));
    }
} 