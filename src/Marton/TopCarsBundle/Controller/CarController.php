<?php
/**
 * Created by PhpStorm.
 * User: Marci
 * Date: 08/11/14
 * Time: 16:30
 */

namespace Marton\TopCarsBundle\Controller;


use Marton\TopCarsBundle\Classes\PriceCalculator;
use Marton\TopCarsBundle\Entity\Car;
use Marton\TopCarsBundle\Entity\User;
use Marton\TopCarsBundle\Repository\CarRepository;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CarController extends Controller{

    // Render Dealership page
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

    // Render Garage page
    public function garageAction(){

        /* @var $user User */
        $user = $this->get('security.context')->getToken()->getUser();
        $cars =  $user->getCars();

        return $this->render('MartonTopCarsBundle:Default:Pages/Subpages/garage.html.twig', array(
            "cars" => $cars,
            "user" => $user
        ));
    }

    // Ajax call to purchase a car
    public function purchaseAction(Request $request){

        // Get entity manager
        $em = $this->getDoctrine()->getManager();

        $error = array();

        // Get id of the car to be purchased
        $car_id = (int) $request->request->get('item');

        // Get car to be purchased
        /* @var $car Car*/
        $car = $em->getRepository('MartonTopCarsBundle:Car')->findOneById(array($car_id));

        // Check if there exists a car with the given ID
        if(sizeof($car) == 0){

            array_push($error, array("Such car does not exist!"));
            $response = new Response(json_encode(array(
                'error' => $error)));
            $response->headers->set('Content-Type', 'application/json');

            return $response;
        }


        // Get user entity
        /* @var $user User */
        $user= $this->get('security.context')->getToken()->getUser();

        // Check if the user has already purchased this car
        if (in_array($car, $user->getCars())){

            array_push($error, array("You already own this car!"));
            $response = new Response(json_encode(array(
                'error' => $error)));
            $response->headers->set('Content-Type', 'application/json');

            return $response;
        }

        $user_gold = $user->getProgress()->getGold();
        $car_price = $car->getPrice();

        // Check if the user can afford the car
        if ($user_gold >= $car_price){

            $user->addCar($car);
            $user->getProgress()->setGold($user_gold - $car_price);
            $em->persist($user);
            $em->flush();
        }else{

            array_push($error, array("You cannot afford this car!"));
            $response = new Response(json_encode(array(
                'error' => $error)));
            $response->headers->set('Content-Type', 'application/json');

            return $response;
        }

        $response = new Response(json_encode(array(
            'error' => $error)));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }
} 