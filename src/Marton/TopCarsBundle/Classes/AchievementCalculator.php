<?php
/**
 * Created by PhpStorm.
 * User: Marci
 * Date: 01/11/14
 * Time: 14:38
 */

namespace Marton\TopCarsBundle\Classes;


class AchievementCalculator {

    private $SCORE_STEP     = 100;
    private $SCORE_POW_BASE = 3;
    private $SCORE_LOG_BASE = 2;

    private $GOLD_BASE      = 5;
    private $GOLD_DIVIDER   = 5;

    // Calculate score needed to reach a given level.
    private function calculateLevelScore($level){
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
        $found_level = false;

        $level = 1;
        $low_score_limit = 0;
        $high_score_limit = 0;
        while (!$found_level){
            $new_level_score = $this->calculateLevelScore($level);
            if ($score >= $new_level_score){
                $low_score_limit = $new_level_score;
                $level++;
            }else{
                $high_score_limit = $new_level_score;
                $found_level = true;
            }
        }

        return array(
            "low_score_limit" => $low_score_limit,
            "high_score_limit" => $high_score_limit,
            "level" => $level-1,
            "score" => $score
        );
    }

    // Printing level details of a specific score.
    public function printLevel(){
        $TEST_SCORE = 28400;
        $level_info = $this->calculateLevel($TEST_SCORE);
        echo "I have " . $TEST_SCORE . " score";
        echo "<br>";
        echo "Level: " . $level_info["level"];
        echo "<br>";
        echo "Low: " . $level_info["low_score_limit"];
        echo "<br>";
        echo "High: " . $level_info["high_score_limit"];
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
}