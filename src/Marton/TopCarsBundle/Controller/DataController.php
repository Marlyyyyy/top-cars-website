<?php
/**
 * Created by PhpStorm.
 * User: Marci
 * Date: 31/10/14
 * Time: 00:17
 */

namespace Marton\TopCarsBundle\Controller;


use Marton\TopCarsBundle\Entity\Car;
use Marton\TopCarsBundle\Repository\CarRepository;
use Marton\TopCarsBundle\Services\PriceCalculator;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DataController extends Controller{

    // Executes a task that only need to be executed once optimally. For example, after changing the database schema,
    // all existing records can be reorganised here, without losing any data.
    public function oneTimeTaskAction(){

        return $this->updateCarImageExtension();
    }

    // Helper function to update all car prices. In case the algorithm that calculates car prices changes within the
    // PriceCalculator service, this script can be called once to update the database with the new prices.
    private function updateCarPrices(){

        /* @var $repository CarRepository */
        $repository = $this->getDoctrine()->getRepository('MartonTopCarsBundle:Car');
        $cars = $repository-> findAllCars();

        /* @var $priceCalculator PriceCalculator */
        $priceCalculator = $this->get('price_calculator');

        /* @var $car Car */
        foreach($cars as $car){

            $car->setPrice($priceCalculator->calculatePrice($car));
        }

        $em = $this->getDoctrine()->getManager();
        $em->flush();

        return $this->render('MartonTopCarsBundle:Default:Pages/home.html.twig');
    }

    // Helper function to update the extension of every car image to be ".png" within the Car table
    private function updateCarImageExtension(){

        /* @var $repository CarRepository */
        $repository = $this->getDoctrine()->getRepository('MartonTopCarsBundle:Car');
        $cars = $repository-> findAllCars();

        /* @var $car Car */
        foreach($cars as $car){

            $carImage = $car->getImage();
            $car->setImage($carImage . ".png");
        }

        $em = $this->getDoctrine()->getManager();
        $em->flush();

        return $this->render('MartonTopCarsBundle:Default:Pages/home.html.twig');
    }
} 
