<?php
/**
 * Created by PhpStorm.
 * User: Marci
 * Date: 19/11/14
 * Time: 17:03
 */

namespace Marton\TopCarsBundle\Tests\Classes;


use Marton\TopCarsBundle\Services\StatisticsCalculator;
use Marton\TopCarsBundle\Entity\User;
use Marton\TopCarsBundle\Entity\UserProgress;

class StatisticsCalculatorUnitTest extends \PHPUnit_Framework_TestCase{

    public function testGetStatistics(){

        // Create new UserProgress
        /* @var $testUserProgress UserProgress */
        $testUserProgress = new UserProgress();
        $testUserProgress->setAllRound(10);
        $testUserProgress->setRoundWin(5);
        $testUserProgress->setRoundLose(5);
        $testUserProgress->setScore(1000);
        $testUserProgress->setLevel(5);
        $testUserProgress->setStreak(5);

        /* @var $testUser User */
        $testUser = new User();
        $testUser->setProgress($testUserProgress);

        /* @var $statisticsCalculator StatisticsCalculator */
        $statisticsCalculator = new StatisticsCalculator();
        $statisticsCalculator->init($testUser);
        $statistics = $statisticsCalculator->getStatistics();

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