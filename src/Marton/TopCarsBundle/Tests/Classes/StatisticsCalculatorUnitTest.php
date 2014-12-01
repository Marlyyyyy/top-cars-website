<?php
/**
 * Created by PhpStorm.
 * User: Marci
 * Date: 19/11/14
 * Time: 17:03
 */

namespace Marton\TopCarsBundle\Tests\Classes;


use Marton\TopCarsBundle\Classes\StatisticsCalculator;
use Marton\TopCarsBundle\Entity\User;
use Marton\TopCarsBundle\Entity\UserProgress;

class StatisticsCalculatorUnitTest extends \PHPUnit_Framework_TestCase{

    public function testGetStatistics(){

        // Create new UserProgress
        /* @var $test_user_progress UserProgress */
        $test_user_progress = new UserProgress();
        $test_user_progress->setAllRound(10);
        $test_user_progress->setRoundWin(5);
        $test_user_progress->setRoundLose(5);
        $test_user_progress->setScore(1000);
        $test_user_progress->setLevel(5);
        $test_user_progress->setStreak(5);

        /* @var $test_user User */
        $test_user = new User();
        $test_user->setProgress($test_user_progress);

        /* @var $statistics_calculator StatisticsCalculator */
        $statistics_calculator = new StatisticsCalculator($test_user);
        $statistics = $statistics_calculator->getStatistics();

        $this->assertEquals(5, $statistics['level']);
        $this->assertEquals(5, $statistics['streak']);
        $this->assertEquals((float) 1, $statistics['wLRatio']);
        $this->assertGreaterThan(0 , $statistics['wLRatioPercentage']);
        $this->assertLessThan(100 , $statistics['wLRatioPercentage']);
        $this->assertEquals(1000, $statistics['score']);
        $this->assertEquals(0, $statistics['draw']);
        $this->assertEquals(100, $statistics['scorePerRound']);
        $this->assertGreaterThan(0, $statistics['scorePerRoundPercentage']);
        $this->assertLessThan(100, $statistics['scorePerRoundPercentage']);
    }
} 