<?php
/**
 * Created by PhpStorm.
 * User: Marci
 * Date: 05/12/14
 * Time: 13:11
 */

namespace Marton\TopCarsBundle\Services;


use Marton\TopCarsBundle\Entity\Car;

class PriceCalculator {

    public function calculatePrice(Car $car){

        $price = 0;
        $price += $car->getSpeed() / 30;
        $price += $car->getPower() / 50;
        $price += $car->getTorque() / 50;
        $price += ($car->getAcceleration() == 0 ? 0 : 50 / $car->getAcceleration());
        $price += ($car->getWeight() == 0 ? 0 : 10000 / $car->getWeight());

        $price = (pow(($price+1)/10, 3.5));
        return round($price);
    }
} 