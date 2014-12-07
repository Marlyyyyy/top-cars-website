<?php
/**
 * Created by PhpStorm.
 * User: Marci
 * Date: 07/12/14
 * Time: 16:24
 */

namespace Marton\TopCarsBundle\Tests\Classes;


use Marton\TopCarsBundle\Entity\Car;
use Marton\TopCarsBundle\Services\PriceCalculator;

class PriceCalculatorUnitTest extends \PHPUnit_Framework_TestCase{

    public function testCalculatePrice(){

        $car1 = new Car();
        $car1->setModel("Ferrari F430");
        $car1->setSpeed("300");
        $car1->setPower("400");
        $car1->setTorque("430");
        $car1->setAcceleration("4.0");
        $car1->setWeight("1500");

        $car2 = new Car();
        $car2->setModel("Ferrari LaFerrari");
        $car2->setSpeed("340");
        $car2->setPower("900");
        $car2->setTorque("950");
        $car2->setAcceleration("2.8");
        $car2->setWeight("1650");

        $priceCalculator = new PriceCalculator();

        $car1Price = $priceCalculator->calculatePrice($car1);
        $car2Price = $priceCalculator->calculatePrice($car2);

        $this->assertGreaterThan($car1Price, $car2Price);
    }
} 