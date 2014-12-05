<?php
/**
 * Created by PhpStorm.
 * User: Marci
 * Date: 05/12/14
 * Time: 13:21
 */

namespace Marton\TopCarsBundle\Services;


use Marton\TopCarsBundle\Entity\User;
use Marton\TopCarsBundle\Entity\UserProgress;

class StatisticsCalculator {

    // This class accepts a User entity, which makes it dependent on User. However, when it comes to changing
    // what data this class should use, one would only have to change code in this class and within the template
    // - instead of in this class, in the template and in each controller as well.

    /**
     * @var User
     */
    private $user;
    /**
     * @var UserProgress
     */
    private $userProgress;

    public function init(User $user){

        $this->user = $user;
        $this->userProgress = $user->getProgress();
    }

    private function calculateWLRatio(){

        $roundWin = $this->userProgress->getRoundWin();
        $roundLose = $this->userProgress->getRoundLose();
        if ($roundLose == 0){
            return 0;
        }else{
            return number_format((float) $roundWin/$roundLose, 2, '.', '');
        }
    }

    private function calculateWLRatioPercentage($WL_ratio){

        return round(50 + ( 10 * (1 - pow(M_E, -(log(abs($WL_ratio), 10))))));
    }

    private function getDraws(){

        $allRound   = $this->userProgress->getAllRound();
        $roundWin   = $this->userProgress->getRoundWin();
        $roundLose  = $this->userProgress->getRoundLose();

        return $allRound - $roundWin - $roundLose;
    }

    private function calculateScorePerRound(){

        $allRound = $this->userProgress->getAllRound();
        if ($allRound === 0){
            return 0;
        }else{
            return round($this->userProgress->getScore() / ($allRound));
        }
    }

    private function calculateScorePerRoundPercentage($scorePerRound){

        // TODO: refine
        return round(50 + ( 10 * (1 - pow(M_E, -(log(abs($scorePerRound), 10))))));
    }

    public function getStatistics(){

        $WL_ratio = $this->calculateWLRatio();
        $WL_ratio_percentage = $this->calculateWLRatioPercentage($WL_ratio);

        $scorePerRound = $this->calculateScorePerRound();
        $scorePerRoundPercentage = $this->calculateScorePerRoundPercentage($scorePerRound);

        return array(
            "level"     => $this->userProgress->getLevel(),
            "streak"    => $this->userProgress->getStreak(),
            "wLRatio"   => $WL_ratio,
            "wLRatioPercentage" => $WL_ratio_percentage,
            "score"     => $this->userProgress->getScore(),
            "draw"      => $this->getDraws(),
            "scorePerRound" => $scorePerRound,
            "scorePerRoundPercentage" => $scorePerRoundPercentage
        );
    }
} 