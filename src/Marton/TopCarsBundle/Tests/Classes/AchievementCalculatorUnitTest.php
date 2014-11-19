<?php
/**
 * Created by PhpStorm.
 * User: Marci
 * Date: 19/11/14
 * Time: 16:33
 */

namespace Marton\TopCarsBundle\Tests\Classes;

use Marton\TopCarsBundle\Classes\AchievementCalculator;

class AchievementCalculatorUnitTest extends \PHPUnit_Framework_TestCase{

    public function testCalculateLevelScore(){

        $achievements_calculator = new AchievementCalculator();

        $test_level_1 = 1;
        $score_needed_1 = $achievements_calculator->calculateLevelScore($test_level_1);
        $test_level_2 = 2;
        $score_needed_2 = $achievements_calculator->calculateLevelScore($test_level_2);

        // First score has to be larger than 0 and lower than the second score
        $this->assertGreaterThan(0, $score_needed_1);
        $this->assertGreaterThan($score_needed_1, $score_needed_2);
    }

    public function testCalculateLevel(){

        $achievements_calculator = new AchievementCalculator();

        $test_level = 1;
        $score_needed = $achievements_calculator->calculateLevelScore($test_level);

        $output_level = $achievements_calculator->calculateLevel($score_needed)['level'];

        $this->assertEquals($test_level, $output_level);
    }

    public function testCalculateGold(){

        $achievements_calculator = new AchievementCalculator();

        $test_level_1 = 1;
        $gold_1 = $achievements_calculator->calculateGold($test_level_1);

        $test_level_2 = 10;
        $gold_2 =  $achievements_calculator->calculateGold($test_level_2);

        $this->assertGreaterThan($gold_1, $gold_2);
    }
} 