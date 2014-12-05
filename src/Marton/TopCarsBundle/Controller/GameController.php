<?php
/**
 * Created by PhpStorm.
 * User: Marci
 * Date: 01/12/14
 * Time: 10:58
 */

namespace Marton\TopCarsBundle\Controller;


use Marton\TopCarsBundle\Entity\User;
use Marton\TopCarsBundle\Entity\UserProgress;
use Marton\TopCarsBundle\Repository\CarRepository;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class GameController extends Controller{

    // Renders Game page
    public function gameAction(){

        // Get entity manager
        $em = $this->getDoctrine()->getManager();

        // Get user entity
        /* @var $user User */
        $user= $this->get('security.context')->getToken()->getUser();

        // Get the progress
        /* @var $user_progress UserProgress */
        $user_progress = $user->getProgress();

        // Get cars owned and selected by the user
        $selected_cars = $em->getRepository('MartonTopCarsBundle:Car')->findSelectedCarsOfUser($user->getId());
        if (count($selected_cars) === 10){
            $is_classic_unlocked = true;
        }else{
            $is_classic_unlocked = false;
        }

        return $this->render('MartonTopCarsBundle:Default:Pages/game.html.twig', array(
            "selected_cars" => $selected_cars,
            "is_classic_unlocked" => $is_classic_unlocked,
            "progress" => $user_progress
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

        $achievementCalculator = $this->get('achievement_calculator');
        $new_score_info = $achievementCalculator->calculateLevel($score);

        /* @var $progress UserProgress */
        $progress->setScore($score);

        // Streak
        $streak = $progress->getStreak();
        $new_streak = (int) $request->request->get('streak');
        if ($new_streak > $streak){

            $progress->setStreak($new_streak);
            $streak = $new_streak;
        }

        // Round Result
        $roundResult = $request->request->get('roundResult');
        $allRound = $progress->getAllRound() + 1;
        $progress->setAllRound($allRound);

        $roundWin = $progress->getRoundWin();
        $roundLose = $progress->getRoundLose();
        switch ($roundResult){
            case "win":
                $roundWin ++;
                $progress->setRoundWin($roundWin);
                break;
            case "lose":
                $roundLose ++;
                $progress->setRoundLose($roundLose);
                break;
        }

        // Skill
        $skill = $achievementCalculator->calculateSkill($score, $allRound, $roundWin, $streak);
        $progress->setSkill($skill);

        // Gold
        $gold = $progress->getGold();
        $old_level = $progress->getLevel();
        $progress->setLevel($new_score_info["level"]);

        if ($old_level<$new_score_info["level"]){

            $extra_gold = 0;
            $level_ups = $new_score_info["level"] - $old_level;
            // TODO: consider leveling up more times
            for ($i=1; $i<$level_ups+1; $i++){
                $extra_gold += $achievementCalculator->calculateGold($old_level + $i);
            }

            $gold += $extra_gold;
            $progress->setGold($gold);
            $level_change = "up";

        }else if($old_level===$new_score_info["level"]){

            $level_change = "stay";

        }else{

            $level_change = "down";
        }

        $em->flush();

        $response = new Response(json_encode(array(
            'levelChange' => $level_change,
            'userLevelInfo' => $new_score_info,
            'gold' => $gold)));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    // Ajax call to return details needed to initialise Free For All game
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
        $achievemetCalculator = $this->get('achievement_calculator');
        $user_level_info = $achievemetCalculator->calculateLevel($user_score);
        $user_level_info["score"] = $user_score;
        $user_level_info["gold"] = $progress->getGold();

        $response = new Response(json_encode(array(
            "deck" => json_encode($cars),
            "user_level_info" => json_encode($user_level_info),)));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    // Ajax call to return details needed to initialise Classic game
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
            $achievemetCalculator = $this->get('achievement_calculator');
            $user_level_info = $achievemetCalculator->calculateLevel($user_score);
            $user_level_info["score"] = $user_score;
            $user_level_info["gold"] = $progress->getGold();

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

    // Ajax call when the user wins a classic game
    public function winClassicAction(Request $request){

        // Get entity manager
        $em = $this->getDoctrine()->getManager();

        $round_result = $request->request->get('round_result');

        $error = array();

        if ($round_result !== "win"){

            array_push($error, "You tried to cheat. Now look at yourself :(");

        }else{

            // Get user entity
            /* @var $user User */
            $user= $this->get('security.context')->getToken()->getUser();

            // Simply give 50 gold to the user
            /* @var $user_progress UserProgress */
            $user_progress = $user->getProgress();
            $user_progress->setGold($user_progress->getGold()+50);

            $em->flush();
        }

        $response = new Response(json_encode(array(
            "error" => $error
        )));

        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }
} 