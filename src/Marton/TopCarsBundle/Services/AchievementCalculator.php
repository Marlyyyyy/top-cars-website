<?php
/**
 * Created by PhpStorm.
 * User: Marci
 * Date: 05/12/14
 * Time: 11:13
 */

namespace Marton\TopCarsBundle\Services;


class AchievementCalculator {

    private $SCORE_STEP     = 100;
    private $SCORE_POW_BASE = 3;
    private $SCORE_LOG_BASE = 2;

    private $GOLD_BASE      = 5;
    private $GOLD_DIVIDER   = 5;

    // Calculate score needed to reach a given level.
    public function calculateLevelScore($level){

        return round($this->SCORE_STEP * pow($this->SCORE_POW_BASE,(1 + log($level, $this->SCORE_LOG_BASE))));
    }

    // Print all levels and the score needed to obtain that.
    public function printAllLevelScore(){

        for ($i=1;$i<100;$i++){
            echo "Level ".$i.": ".$this->calculateLevelScore($i);
            echo "<br>";
        }
    }

    // Calculate the level from a given score. Returns an array describing the level.
    public function calculateLevel($score){

        $foundLevel = false;

        $level = 1;
        $lowScoreLimit = 0;
        $highScoreLimit = 0;
        while (!$foundLevel){
            $newLevelScore = $this->calculateLevelScore($level);
            if ($score >= $newLevelScore){
                $lowScoreLimit = $newLevelScore;
                $level++;
            }else{
                $highScoreLimit = $newLevelScore;
                $foundLevel = true;
            }
        }

        return array(
            "low_score_limit" => $lowScoreLimit,
            "high_score_limit" => $highScoreLimit,
            "level" => $level-1,
            "score" => $score
        );
    }

    // Printing level details of a specific score.
    public function printLevel(){

        $TEST_SCORE = 28400;
        $levelInfo = $this->calculateLevel($TEST_SCORE);
        echo "I have " . $TEST_SCORE . " score";
        echo "<br>";
        echo "Level: " . $levelInfo["level"];
        echo "<br>";
        echo "Low: " . $levelInfo["low_score_limit"];
        echo "<br>";
        echo "High: " . $levelInfo["high_score_limit"];
    }

    // Calculate the amount of gold received at a given new level.
    public function calculateGold($level){

        return ceil($level / $this->GOLD_DIVIDER) * $this->GOLD_BASE;
    }

    // Printing the amount of gold received at every level
    public function printGoldPerLevel(){

        echo "Amount of gold per level:";
        echo "<br>";
        for ($i=0;$i<100;$i++){
            echo "Rank up to level: " . $i . ", Gold received: " . $this->calculateGold($i);
            echo "<br>";
        }
    }

    // Calculate and return new skill from parameters
    public function calculateSkill($score, $allRound, $roundWin, $streak){

        if (($allRound-$roundWin) > 0){

            $skill = round($score/($allRound-$roundWin));
            $skill = $skill * log($streak+1, 2);
        }else{

            $skill = 0;
        }

        return $skill;
    }
} 