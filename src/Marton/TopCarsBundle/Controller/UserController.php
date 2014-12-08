<?php
/**
 * Created by PhpStorm.
 * User: Marci
 * Date: 08/11/14
 * Time: 16:15
 */

namespace Marton\TopCarsBundle\Controller;

use Marton\TopCarsBundle\Entity\Car;
use Marton\TopCarsBundle\Entity\User;
use Marton\TopCarsBundle\Entity\UserDetails;
use Marton\TopCarsBundle\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class UserController extends Controller{

    // Render the Leaderboard page
    public function leaderboardAction($sort, $username){

        /* @var $repository UserRepository */
        $repository = $this->getDoctrine()->getRepository('MartonTopCarsBundle:User');
        $users = $repository-> findHighscores($sort, $username);

        /* @var $user User */
        foreach ($users as $user){
            $statisticsCalculator =  $this->get('statistics_calculator');
            $statisticsCalculator->init($user);
            $user->statistics = $statisticsCalculator->getStatistics();
        }

        return $this->render('MartonTopCarsBundle:Default:Pages/leaderboard.html.twig', array(
            'users' => $users,
            'sort' => $sort,
            'username' => $username
        ));
    }

    // Handles submitted form when searching for users on the Leaderboard page
    public function searchAction(Request $request){

        $postData = $request->request->all();
        $username = $postData['username'];
        $sort     = $postData['sort'];

        return $this->redirect($this->generateUrl('marton_topcars_leaderboard', array(
            'username' => $username,
            'sort' => $sort
        )));
    }

    // Renders the User profile page
    public function profileAction($user){

        /* @var $repository UserRepository */
        $repository = $this->getDoctrine()->getRepository('MartonTopCarsBundle:User');
        /* @var $userDetails UserDetails */
        $userDetails = $repository-> findDetailsOfUser($user);

        $statisticsCalculator =  $this->get('statistics_calculator');
        $statisticsCalculator->init($userDetails);
        $userDetails->statistics = $statisticsCalculator->getStatistics();

        $cars = $userDetails->getCars();
        $carsValue = 0;

        /* @var $car Car */
        foreach($cars as $car){
            $carsValue += $car->getPrice();
        }

        $allUsersOrdered = $repository->findAllIdsOrderedBySkill();
        $rank = array_search($userDetails->getId(), $allUsersOrdered) +1;

        return $this->render('MartonTopCarsBundle:Default:Pages/user.html.twig', array(
            'user' => $userDetails,
            'cars' => $cars,
            'cars_value' => $carsValue,
            'rank' => $rank
        ));
    }
} 