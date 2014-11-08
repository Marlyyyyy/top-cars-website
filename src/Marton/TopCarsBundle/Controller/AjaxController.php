<?php
/**
 * Created by PhpStorm.
 * User: Marci
 * Date: 02/11/14
 * Time: 20:40
 */

namespace Marton\TopCarsBundle\Controller;


use Doctrine\Common\Collections\ArrayCollection;
use Marton\TopCarsBundle\Classes\AchievementCalculator;
use Marton\TopCarsBundle\Entity\User;
use Marton\TopCarsBundle\Entity\UserProgress;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AjaxController extends Controller{

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