<?php
/**
 * Created by PhpStorm.
 * User: Marci
 * Date: 19/11/14
 * Time: 16:33
 */

namespace Marton\TopCarsBundle\Tests\Classes;

use Marton\TopCarsBundle\Services\AchievementCalculator;

class AchievementCalculatorUnitTest extends \PHPUnit_Framework_TestCase{

    public function testCalculateLevelScore(){

        $achievementCalculator = new AchievementCalculator();

        $testLevel1 = 1;
        $scoreNeeded1 = $achievementCalculator->calculateLevelScore($testLevel1);
        $testLevel2 = 2;
        $scoreNeeded2 = $achievementCalculator->calculateLevelScore($testLevel2);

        // First score has to be larger than 0 and lower than the second score
        $this->assertGreaterThan(0, $scoreNeeded1);
        $this->assertGreaterThan($scoreNeeded1, $scoreNeeded2);
    }

    public function testCalculateLevel(){

        $achievementCalculator = new AchievementCalculator();

        $testLevel = 1;
        $score_needed = $achievementCalculator->calculateLevelScore($testLevel);

        $outputLevel = $achievementCalculator->calculateLevel($score_needed)['level'];

        $this->assertEquals($testLevel, $outputLevel);
    }

    public function testCalculateGold(){

        $achievementCalculator = new AchievementCalculator();

        $testLevel1 = 1;
        $gold1 = $achievementCalculator->calculateGold($testLevel1);

        $testLevel2 = 10;
        $gold2 =  $achievementCalculator->calculateGold($testLevel2);

        $this->assertGreaterThan($gold1, $gold2);
    }
} 