<?php
/**
 * Created by PhpStorm.
 * User: Marci
 * Date: 08/11/14
 * Time: 16:30
 */

namespace Marton\TopCarsBundle\Controller;


use Doctrine\Common\Collections\ArrayCollection;
use Marton\TopCarsBundle\Entity\Car;
use Marton\TopCarsBundle\Entity\User;
use Marton\TopCarsBundle\Entity\UserProgress;
use Marton\TopCarsBundle\Repository\CarRepository;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class CarController extends Controller{

    // Renders the Dealership page to display either available or all cars according to the parameter. In both cases,
    // only those cars are fetched from the database, which are not owned by the user yet.
    public function dealershipAction($option){

        /* @var $user User */
        $user = $this->get('security.context')->getToken()->getUser();

        // Get a list of requested cars
        /* @var $repository CarRepository */
        $repository = $this->getDoctrine()->getRepository('MartonTopCarsBundle:Car');
        
        if ($option === "all"){
            $cars = $repository-> findAllNotUserCars($user->getCars());
        }else{
            /* @var $userProgress UserProgress */
            $userProgress = $user->getProgress();
            $userGold = $userProgress->getGold();
            $userCars = $user->getCars();
            $cars = $repository-> findAllNotUserCarsWherePriceLessThan($userGold, $userCars);
        }

        return $this->render('MartonTopCarsBundle:Default:Pages/Subpages/dealership.html.twig', array(
            "cars" => $cars,
            "user" => $user,
            "empty" => count($cars) === 0 ? true : false,
            "option" => $option,
            "available_active" => $option === "available" ? " active" : "",
            "all_active" => $option === "all" ? " active" : ""
        ));
    }

    // Renders the Garage page by fetching all cars owned by the user, tagging them if they were selected by the user,
    // and passing them to the template.
    public function garageAction(){

        /* @var $user User */
        $user = $this->get('security.context')->getToken()->getUser();
        $cars =  $user->getCars();

        // Get collection of already selected cars
        /* @var $selectedCars ArrayCollection */
        $selectedCars = $user->getSelectedCars();
        $selectedCarsCount = count($selectedCars);

        $allCarsCount = $this->getDoctrine()->getRepository('MartonTopCarsBundle:Car')->countAllCars();

        // Tag cars that have already been selected
        foreach($cars as $car){

            if($selectedCars->contains($car)){
                $car->selected = true;
            }else{
                $car->selected = false;
            }
        }

        return $this->render('MartonTopCarsBundle:Default:Pages/Subpages/garage.html.twig', array(
            "cars" => $cars,
            "user" => $user,
            "selected_cars_count" => $selectedCarsCount,
            "all_cars_count" => $allCarsCount
        ));
    }

    // Handles Ajax POST request to purchase a car as long as: the car has a valid ID, the car hasn't been purchased by
    // the user yet and if the user can afford the car.
    public function purchaseAction(Request $request){

        // Get id of the car to be purchased
        $carId = (int) $request->request->get('item');

        // Get car to be purchased
        /* @var $car Car*/
        $em = $this->getDoctrine()->getManager();
        $car = $em->getRepository('MartonTopCarsBundle:Car')->findOneById(array($carId));

        $error = array();

        // Check if there exists a car with the given ID
        if(sizeof($car) == 0){

            array_push($error, array("Such car does not exist!"));
            return new JsonResponse(array('error' => $error));
        }

        /* @var $user User */
        $user = $this->get('security.context')->getToken()->getUser();

        // Check if the user has already purchased this car
        if (in_array($car, $user->getCars())){

            array_push($error, array("You already own this car!"));
            return new JsonResponse(array('error' => $error));
        }

        /* @var $userProgress UserProgress */
        $userProgress = $user->getProgress();
        $userGold = $userProgress->getGold();
        $carPrice = $car->getPrice();

        // Check if the user can afford the car
        if ($userGold >= $carPrice){

            $user->addCar($car);
            $userProgress->setGold($userGold - $carPrice);
            $em->flush();

        }else{

            array_push($error, array("You cannot afford this car!"));
            return new JsonResponse(array('error' => $error));
        }

        return new JsonResponse(array('error' => $error));
    }

    // Handles Ajax POST request to select a car as long as: the car has a valid ID, the car is owned by the user and
    // the car hasn't been selected yet.
    public function selectAction(Request $request){
        
        // Get id of the car to be selected
        $carId = (int) $request->request->get('item');

        // Get car to be selected
        /* @var $car Car*/
        $em = $this->getDoctrine()->getManager();
        $car = $em->getRepository('MartonTopCarsBundle:Car')->findOneById(array($carId));

        $error = array();
        
        // Check if there exists a car with the given ID
        if(sizeof($car) == 0){

            array_push($error, array("Such car does not exist!"));
            return new JsonResponse(array('error' => $error));
        }

        /* @var $user User */
        $user= $this->get('security.context')->getToken()->getUser();

        // Check if the user owns this car
        if(!in_array($car, $user->getCars())){

            array_push($error, array("You haven't purchased this car yet!"));
            return new JsonResponse(array('error' => $error));
        }

        $selectedCars = $user->getSelectedCars();
        $selectedCarsCount = count($selectedCars);
        $isFull = false;

        $change = "";
        
        // Check if the user has already selected this car
        if ($selectedCars->contains($car)){

            $change = "remove";

            $user->removeSelectedCars($car);
            $em->flush();

            $selectedCarsCount--;
        }else{

            // Check how many cars the user has already selected
            if ($selectedCarsCount < 10){

                $change = "add";

                $user->addSelectedCars($car);
                $em->flush();

                $selectedCarsCount++;

                if ($selectedCarsCount === 10) $isFull = true;
            }else{

                $isFull = true;
                array_push($error, "You have already selected 10 cars!");
            }
        }

        return new JsonResponse(array(
            'error' => $error,
            'change' => $change,
            'no_of_cars' => $selectedCarsCount,
            'is_full' => $isFull
        ));
    }

    // Handle Ajax POST request to unselect all cars
    public function unselectAllAction(Request $request){

        /* @var $user User */
        $user= $this->get('security.context')->getToken()->getUser();

        $selectedCars = $user->getSelectedCars();

        foreach($selectedCars as $car){

            $user->removeSelectedCars($car);
        }

        $em = $this->getDoctrine()->getManager();
        $em->flush();

        return new JsonResponse(array());
    }
} 