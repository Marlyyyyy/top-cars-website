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

    // This class accepts a User object, which makes it dependent on the User class. However, when it comes to changing
    // what data this service should use to calculate the statistics, one would only have to change the code here
    // - instead of here AND in each controller separately.

    /**
     * @var User
     */
    private $user;
    /**
     * @var UserProgress
     */
    private $userProgress;

    /**
     * Initalises the service.
     * @param User $user
     * @return void
     */
    public function init(User $user){

        $this->user = $user;
        $this->userProgress = $user->getProgress();
    }

    /**
     * Returns an array of statistical values
     * @return array
     */
    public function getStatistics(){

        $WlRatio = $this->calculateWLRatio();
        $WlRatioPercentage = $this->calculateWLRatioPercentage($WlRatio);

        $scorePerRound = $this->calculateScorePerRound();
        $scorePerRoundPercentage = $this->calculateScorePerRoundPercentage($scorePerRound);

        return array(
            "level"     => $this->userProgress->getLevel(),
            "streak"    => $this->userProgress->getStreak(),
            "wLRatio"   => $WlRatio,
            "wLRatioPercentage" => $WlRatioPercentage,
            "score"     => $this->userProgress->getScore(),
            "draw"      => $this->getDraws(),
            "scorePerRound" => $scorePerRound,
            "scorePerRoundPercentage" => $scorePerRoundPercentage
        );
    }

    /**
     * Calculates Win/Loss ratio
     * @return float
     */
    private function calculateWLRatio(){

        $roundWin = $this->userProgress->getRoundWin();
        $roundLose = $this->userProgress->getRoundLose();
        if ($roundLose == 0){
            return 0;
        }else{
            return number_format((float) $roundWin/$roundLose, 2, '.', '');
        }
    }

    /**
     * Calculates the % that can be used within the user profile as an indicator for how good the user's WL ratio is.
     * @param float $WlRatio
     * @return int
     */
    private function calculateWLRatioPercentage($WlRatio){

        return round(50 + ( 10 * (1 - pow(M_E, -(log(abs($WlRatio), 10))))));
    }

    /**
     * Returns the number of draws calculated using Wins and Losses
     * @return int
     */
    private function getDraws(){

        $allRound   = $this->userProgress->getAllRound();
        $roundWin   = $this->userProgress->getRoundWin();
        $roundLose  = $this->userProgress->getRoundLose();

        return $allRound - $roundWin - $roundLose;
    }

    /**
     * Calculates the ratio of Score and AllRound
     * @return int
     */
    private function calculateScorePerRound(){

        $allRound = $this->userProgress->getAllRound();
        if ($allRound === 0){
            return 0;
        }else{
            return round($this->userProgress->getScore() / ($allRound));
        }
    }

    /**
     * Calculates the % that can be used within the user profile as an indicator for how good the user's SPR ratio is.
     * @param int $scorePerRound
     * @return int
     */
    private function calculateScorePerRoundPercentage($scorePerRound){

        return round(50 + ( 10 * (1 - pow(M_E, -(log(abs($scorePerRound), 10))))));
    }
} 