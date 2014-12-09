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
use Marton\TopCarsBundle\Services\AchievementCalculator;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class GameController extends Controller{

    // Renders the Game page by first checking if the Classic game mode is unlocked for the player.
    public function gameAction(){

        /* @var $user User */
        $user= $this->get('security.context')->getToken()->getUser();

        /* @var $userProgress UserProgress */
        $userProgress = $user->getProgress();

        // Get cars owned and selected by the user
        $em = $this->getDoctrine()->getManager();
        $selectedCars = $em->getRepository('MartonTopCarsBundle:Car')->findSelectedCarsOfUser($user->getId());
        
        if (count($selectedCars) === 10){
            $isClassicUnlocked = true;
        }else{
            $isClassicUnlocked = false;
        }

        return $this->render('MartonTopCarsBundle:Default:Pages/game.html.twig', array(
            "selected_cars" => $selectedCars,
            "is_classic_unlocked" => $isClassicUnlocked,
            "progress" => $userProgress
        ));
    }

    // Handles Ajax POST request to update the user's UserProgress during a game. This includes: Score, Level, Streak,
    // AllRound, RoundWin, RoundLose, Skill and Gold. It returns a response containing and indication whether the
    // user has levelled up and information about her current level.
    public function postUserScoreAction(Request $request){

        /* @var $user User */
        $user= $this->get('security.context')->getToken()->getUser();
        /* @var $progress UserProgress */
        $progress = $user->getProgress();

        // Update Score and Level
        $score = (int) $request->request->get('score');
        $progress->setScore($score);

        /* @var $achievementCalculator AchievementCalculator */
        $achievementCalculator = $this->get('achievement_calculator');
        $newScoreInfo = $achievementCalculator->calculateLevel($score);

        // Update Streak
        $streak = $progress->getStreak();
        $newStreak = (int) $request->request->get('streak');
        
        if ($newStreak > $streak){

            $progress->setStreak($newStreak);
            $streak = $newStreak;
        }

        // Update Round Result
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

        // Update Skill
        $skill = $achievementCalculator->calculateSkill($score, $allRound, $roundWin, $streak);
        $progress->setSkill($skill);

        // Update Gold
        $gold = $progress->getGold();
        $oldLevel = $progress->getLevel();
        $progress->setLevel($newScoreInfo["level"]);

        if ($oldLevel < $newScoreInfo["level"]){

            $extraGold = 0;
            $levelUps = $newScoreInfo["level"] - $oldLevel;
            for ($i = 1; $i < $levelUps + 1; $i++){
                $extraGold += $achievementCalculator->calculateGold($oldLevel + $i);
            }

            $gold += $extraGold;
            $progress->setGold($gold);
            $levelChange = "up";

        }else if($oldLevel === $newScoreInfo["level"]){
            $levelChange = "stay";
        }else{
            $levelChange = "down";
        }

        $em = $this->getDoctrine()->getManager();
        $em->flush();

        return new JsonResponse(array(
            'levelChange' => $levelChange,
            'userLevelInfo' => $newScoreInfo,
            'gold' => $gold
        ));
    }

    // Handles Ajax POST request to return details needed to initialise the Free For All game. These details are:
    // all cars and information about the user's current level.
    public function checkFreeForAllAction(Request $request){

        /* @var $user User */
        $user = $this->get('security.context')->getToken()->getUser();

        /* @var $progress UserProgress */
        $progress = $user->getProgress();
        $userScore = $progress->getScore();

        /* @var $carRepository CarRepository */
        $carRepository = $this->getDoctrine()->getRepository('MartonTopCarsBundle:Car');
        $cars = $carRepository-> findAllCarsAsArray();

        // Calculate score initially
        /* @var $achievementCalculator AchievementCalculator */
        $achievemetCalculator = $this->get('achievement_calculator');
        $userLevelInfo = $achievemetCalculator->calculateLevel($userScore);
        $userLevelInfo["score"] = $userScore;
        $userLevelInfo["gold"] = $progress->getGold();

        return new JsonResponse(array(
            "deck" => json_encode($cars),
            "user_level_info" => json_encode($userLevelInfo)
        ));
    }

    // Handles Ajax POST request to return details needed to initialise Classic game. These details are: all cars,
    // 10 cars selected by the user and information about the user's current level.
    public function checkClassicAction(Request $request){

        /* @var $user User */
        $user = $this->get('security.context')->getToken()->getUser();

        // Get cars owned and selected by the user
        $em = $this->getDoctrine()->getManager();
        $selectedCars = $em->getRepository('MartonTopCarsBundle:Car')->findSelectedCarsOfUser($user->getId());

        // Check if the user has enough cars to play the game
        if(count($selectedCars) === 10){

            /* @var $user User */
            /* @var $progress UserProgress */
            $progress = $user->getProgress();
            $userScore = $progress->getScore();

            /* @var $carRepository CarRepository */
            $carRepository = $this->getDoctrine()->getRepository('MartonTopCarsBundle:Car');
            $cars = $carRepository-> findAllCarsAsArray();

            // Calculate score initially
            /* @var $achievementCalculator AchievementCalculator */
            $achievemetCalculator = $this->get('achievement_calculator');
            $userLevelInfo = $achievemetCalculator->calculateLevel($userScore);
            $userLevelInfo["score"] = $userScore;
            $userLevelInfo["gold"] = $progress->getGold();

            return new JsonResponse(array(
                "error" => array(),
                "deck" => json_encode($cars),
                "selected_cars" => json_encode($selectedCars),
                "user_level_info" => json_encode($userLevelInfo)
            ));
        }else{

            $error = array();
            array_push($error, array("You do not have enough cars selected to play this game mode"));
            return new JsonResponse(array('error' => $error));
        }
    }

    // Handles Ajax POST request that is sent when the user wins a classic game. It gives 50 gold to the user as reward.
    public function winClassicAction(Request $request){

        $roundResult = $request->request->get('round_result');

        $error = array();

        if ($roundResult !== "win"){

            array_push($error, "You tried to cheat. Now look at yourself :(");

        }else{

            /* @var $user User */
            $user= $this->get('security.context')->getToken()->getUser();

            // Simply give 50 gold to the user
            /* @var $userProgress UserProgress */
            $userProgress = $user->getProgress();
            $userProgress->setGold($userProgress->getGold()+50);

            $em = $this->getDoctrine()->getManager();
            $em->flush();
        }

        return new JsonResponse(array('error' => $error));
    }
} 