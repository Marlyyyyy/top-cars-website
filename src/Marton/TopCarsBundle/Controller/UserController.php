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
use Marton\TopCarsBundle\Entity\UserProgress;
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

        $garage = $user_details[0]->getCars();

        $priceCalculator = new PriceCalculator();

        return $this->render('MartonTopCarsBundle:Default:Pages/user.html.twig', array(
            'user' => $user_details[0],
            'cars' => $priceCalculator->assignPrices($garage)
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
} 