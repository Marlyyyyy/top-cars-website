<?php
/**
 * Created by PhpStorm.
 * User: Marci
 * Date: 27/10/14
 * Time: 22:01
 */

namespace Marton\TopCarsBundle\Controller;

use Marton\TopCarsBundle\Classes\PriceCalculator;
use Marton\TopCarsBundle\Entity\User;
use Marton\TopCarsBundle\Repository\CarRepository;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class PageController extends Controller {

    // Home page
    public function homeAction(){

        return $this->render('MartonTopCarsBundle:Default:Pages/home.html.twig');
    }

    // Cars -> Dealership page
    public function dealershipAction($option){

        // Get the user
        /* @var $user User */
        $user = $this->get('security.context')->getToken()->getUser();

        // Get all cars
        /* @var $repository CarRepository */
        $repository = $this->getDoctrine()->getRepository('MartonTopCarsBundle:Car');
        if ($option === "all"){
            $cars = $repository-> findAllNotUserCars($user->getCars());
        }else{
            $cars = $repository-> findAllNotUserCarsWherePriceLessThan($user->getProgress()->getGold(), $user->getCars());
        }

        $priceCalculator = new PriceCalculator();

        return $this->render('MartonTopCarsBundle:Default:Pages/Subpages/dealership.html.twig', array(
            "cars" => $priceCalculator->assignPrices($cars),
            "user" => $user,
            "empty" => count($cars) === 0 ? true : false,
            "option" => $option,
            "available_active" => $option === "available" ? " active" : "",
            "all_active" => $option === "all" ? " active" : ""
        ));
    }

    // Cars -> Garage page
    public function garageAction(){

        /* @var $user User */
        $user = $this->get('security.context')->getToken()->getUser();
        $cars =  $user->getCars();

        return $this->render('MartonTopCarsBundle:Default:Pages/Subpages/garage.html.twig', array(
            "cars" => $cars,
            "user" => $user
        ));
    }
} 