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

    public function calculatePrice(Car $card){

        $price = 0;
        $price += $card->getSpeed() / 30;
        $price += $card->getPower() / 50;
        $price += $card->getTorque() / 50;
        $price += ($card->getAcceleration() == 0 ? 0 : 50 / $card->getAcceleration());
        $price += ($card->getWeight() == 0 ? 0 : 10000 / $card->getWeight());

        $price = (pow(($price+1)/10, 3.5));
        return round($price);
    }
} 