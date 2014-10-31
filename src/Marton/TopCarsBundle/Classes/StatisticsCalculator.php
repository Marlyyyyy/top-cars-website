<?php
/**
 * Created by PhpStorm.
 * User: Marci
 * Date: 31/10/14
 * Time: 23:35
 */

namespace Marton\TopCarsBundle\Classes;


use Marton\TopCarsBundle\Entity\User;
use Marton\TopCarsBundle\Entity\UserProgress;

class StatisticsCalculator {

    private $user;
    /**
     * @var UserProgress
     */
    private $userProgress;

    function __construct(User $user){
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

    public function getStatistics(){
        return array(
            "level"     => $this->userProgress->getLevel(),
            "streak"    => $this->userProgress->getStreak(),
            "wLRatio"   => $this->calculateWLRatio(),
            "score"     => $this->userProgress->getScore(),
            "draw"      => $this->getDraws(),
            "scorePerRound" => $this->calculateScorePerRound()
        );
    }
} 