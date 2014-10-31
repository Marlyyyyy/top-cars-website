<?php
/**
 * Created by PhpStorm.
 * User: Marci
 * Date: 31/10/14
 * Time: 00:07
 */

namespace Marton\TopCarsBundle\Classes;

use Marton\TopCarsBundle\Entity\Car;

class PriceCalculator {

    public function assignPrices(array $cards){

        /* @var $card Car */
        foreach ($cards as $card){
            $price = $this->calculatePrice($card);
            $card->setPrice($price);
        }

        return  $cards;
    }

    public function calculatePrice(Car $card){

        $price = 0;
        $price += $card->getSpeed() / 30;
        $price += $card->getPower() / 50;
        $price += $card->getTorque() / 50;
        $price += 50 / $card->getAcceleration();
        $price += 10000 / $card->getWeight();

        $price = (pow(($price+1)/10, 3.5));
        return round($price);
    }
} 