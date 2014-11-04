<?php
/**
 * Created by PhpStorm.
 * User: Marci
 * Date: 27/10/14
 * Time: 22:01
 */

namespace Marton\TopCarsBundle\Controller;

use Marton\TopCarsBundle\Classes\AchievementCalculator;
use Marton\TopCarsBundle\Classes\PriceCalculator;
use Marton\TopCarsBundle\Classes\StatisticsCalculator;
use Marton\TopCarsBundle\Entity\SuggestedCar;
use Marton\TopCarsBundle\Entity\User;
use Marton\TopCarsBundle\Entity\UserProgress;
use Marton\TopCarsBundle\Repository\CarRepository;
use Marton\TopCarsBundle\Repository\UserProgressRepository;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class PageController extends Controller {

    // Home page
    public function homeAction(){

        return $this->render('MartonTopCarsBundle:Default:Pages/home.html.twig');
    }

    public function gameAction(){

        // Get the user
        $user = $this->get('security.context')->getToken()->getUser();

        // Get the progress
        /* @var $user User */
        /* @var $progress UserProgress */
        $progress = $user->getProgress();
        $user_score = $progress->getScore();

        // Get all cars
        /* @var $repository CarRepository */
        $repository = $this->getDoctrine()->getRepository('MartonTopCarsBundle:Car');
        $cars = $repository-> findAllCarsAsArray();

        // To calculate score initially
        $achievemetCalculator = new AchievementCalculator();
        $user_level_info = $achievemetCalculator->calculateLevel($user_score);
        $user_level_info["score"] = $user_score;

        // Test
        //$achievemetCalculator->printAllLevelScore();
        //$achievemetCalculator->printLevel();

        return $this->render('MartonTopCarsBundle:Default:Pages/game.html.twig', array(
            "deck" => json_encode($cars),
            "user_level_info" => json_encode($user_level_info)
        ));
    }

    public function leaderboardAction(){

        // Get all highscores
        /* @var $repository UserProgressRepository */
        $repository = $this->getDoctrine()->getRepository('MartonTopCarsBundle:UserProgress');
        $users = $repository-> findHighscores();

        foreach ($users as $user){
            $statisticsCalculator = new StatisticsCalculator($user);
            /* @var $user User */
            $user->setStatistics($statisticsCalculator->getStatistics());
        }

        return $this->render('MartonTopCarsBundle:Default:Pages/leaderboard.html.twig', array(
            'users' => $users
        ));
    }

    // User page
    public function userAction($user){

        // Get details of this user
        /* @var $repository UserProgressRepository */
        $repository = $this->getDoctrine()->getRepository('MartonTopCarsBundle:UserProgress');
        $user_details = $repository-> findDetailsOfUser($user);

        $statisticsCalculator = new StatisticsCalculator($user_details[0]);
        $user_details[0]->setStatistics($statisticsCalculator->getStatistics());

        $garage = $user_details[0]->getCars();

        $priceCalculator = new PriceCalculator();

        return $this->render('MartonTopCarsBundle:Default:Pages/user.html.twig', array(
            'user' => $user_details[0],
            'cars' => $priceCalculator->assignPrices($garage)
        ));
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

    // Suggest page
    public function suggestAction(Request $request){

        $suggested_car = new SuggestedCar();
        $form = $this->createFormBuilder($suggested_car)
            ->add('model', 'text')
            ->add('image', 'file')
            ->add('speed', 'text')
            ->add('power', 'text')
            ->add('torque', 'text')
            ->add('acceleration', 'text')
            ->add('weight', 'text')
            ->add('comment', 'text')
            ->add('save', 'submit', array('label' => 'Submit'))
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()){

            $image_file = $suggested_car->getImage();

            $new_path = $this->get('kernel')->getRootDir() . '/../web/bundles/martontopcars/images/card_game_suggest';

            $image_file->move($new_path, $image_file->getClientOriginalName());

            $suggested_car->setImage($image_file->getClientOriginalName());

            $image_file = null;

            // Get the user
            /* @var $user User */
            $user = $this->get('security.context')->getToken()->getUser();

            $user->addSuggestedCar($suggested_car);

            $em = $this->getDoctrine()->getManager();
            $em->flush();

            return $this->render('MartonTopCarsBundle:Default:Pages/home.html.twig');
        }else{
            return $this->render('MartonTopCarsBundle:Default:Pages/suggest.html.twig', array(
                'form' => $form->createView()
            ));
        }
    }
} 