<?php
/**
 * Created by PhpStorm.
 * User: Marci
 * Date: 31/10/14
 * Time: 00:17
 */

namespace Marton\TopCarsBundle\Controller;


use Marton\TopCarsBundle\Classes\PriceCalculator;
use Marton\TopCarsBundle\Entity\Car;
use Marton\TopCarsBundle\Repository\CarRepository;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DataController extends Controller{

    public function oneTimeTaskAction($task){

        /* @var $repository CarRepository */
        $repository = $this->getDoctrine()->getRepository('MartonTopCarsBundle:Car');
        $cars = $repository-> findAllCars();

        $priceCalculator = new PriceCalculator();

        return $this->render('MartonTopCarsBundle:Default:Pages/home.html.twig');
    }
} 
