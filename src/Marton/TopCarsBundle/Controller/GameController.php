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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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
        if (count($selected_cars) === 10){
            $is_classic_unlocked = true;
        }else{
            $is_classic_unlocked = false;
        }

        // To calculate score initially
        $achievemetCalculator = new AchievementCalculator();
        $user_level_info = $achievemetCalculator->calculateLevel($user_score);
        $user_level_info["score"] = $user_score;

        // Test
        //$achievemetCalculator->printAllLevelScore();
        //$achievemetCalculator->printLevel();

        return $this->render('MartonTopCarsBundle:Default:Pages/game.html.twig', array(
            "selected_cars" => $selected_cars,
            "is_classic_unlocked" => $is_classic_unlocked
        ));
    }

    // Ajax call to update the user's score during a game
    public function postUserScoreAction(Request $request){

        // Get entity manager
        $em = $this->getDoctrine()->getManager();

        // Get user entity
        /* @var $user User */
        $user= $this->get('security.context')->getToken()->getUser();
        $progress = $user->getProgress();

        // Score and Level
        $score = (int) $request->request->get('score');

        $achievementCalculator = new AchievementCalculator();
        $new_score_info = $achievementCalculator->calculateLevel($score);

        /* @var $progress UserProgress */
        $progress->setScore($score);

        $old_level = $progress->getLevel();
        $progress->setLevel($new_score_info["level"]);

        // Streak
        $streak = (int) $request->request->get('streak');
        $old_streak = $progress->getStreak();
        if ($streak > $old_streak) $progress->setStreak($streak);

        // Round Result
        $roundResult = $request->request->get('roundResult');
        $old_allRound = $progress->getAllRound();
        $progress->setAllRound($old_allRound + 1);
        switch ($roundResult){
            case "win":
                $progress->setRoundWin($progress->getRoundWin()+1);
                break;
            case "lose":
                $progress->setRoundLose($progress->getRoundLose()+1);
                break;
        }

        if ($old_level<$new_score_info["level"]){
            $level_change = "up";
            $progress->setGold($progress->getGold() + $achievementCalculator->calculateGold($progress->getLevel()));
        }else if($old_level===$new_score_info["level"]){
            $level_change = "stay";
        }else{
            $level_change = "down";
        }

        $em->flush();

        $response = new Response(json_encode(array(
            'levelChange' => $level_change,
            'userLevelInfo' => $new_score_info)));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    public function checkFreeForAllAction(Request $request){

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

        // To calculate score initially
        $achievemetCalculator = new AchievementCalculator();
        $user_level_info = $achievemetCalculator->calculateLevel($user_score);
        $user_level_info["score"] = $user_score;

        $response = new Response(json_encode(array(
            "deck" => json_encode($cars),
            "user_level_info" => json_encode($user_level_info))));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    public function checkClassicAction(Request $request){

        // Get entity manager
        $em = $this->getDoctrine()->getManager();

        // Get user entity
        /* @var $user User */
        $user= $this->get('security.context')->getToken()->getUser();

        // Get cars owned and selected by the user
        $selected_cars = $em->getRepository('MartonTopCarsBundle:Car')->findSelectedCarsOfUser($user->getId());

        // Check if the user has enough cars to play the game
        if(count($selected_cars) === 10){

            // Get the progress
            /* @var $user User */
            /* @var $progress UserProgress */
            $progress = $user->getProgress();
            $user_score = $progress->getScore();

            // Get all cars
            /* @var $repository CarRepository */
            $repository = $this->getDoctrine()->getRepository('MartonTopCarsBundle:Car');
            $cars = $repository-> findAllCarsAsArray();

            // To calculate score initially
            $achievemetCalculator = new AchievementCalculator();
            $user_level_info = $achievemetCalculator->calculateLevel($user_score);
            $user_level_info["score"] = $user_score;

            $response = new Response(json_encode(array(
                "error" => array(),
                "deck" => json_encode($cars),
                "selected_cars" => json_encode($selected_cars),
                "user_level_info" => json_encode($user_level_info))));
        }else{

            $response = new Response(json_encode(array(
                "error" => array("You do not have enough cars selected to play this game mode")
            )));
        }

        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }
} 