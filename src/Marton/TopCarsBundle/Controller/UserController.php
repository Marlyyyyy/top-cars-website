<?php
/**
 * Created by PhpStorm.
 * User: Marci
 * Date: 08/11/14
 * Time: 16:15
 */

namespace Marton\TopCarsBundle\Controller;

use Marton\TopCarsBundle\Entity\User;
use Marton\TopCarsBundle\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class UserController extends Controller{

    // Renders Leaderboard page
    public function leaderboardAction($sort, $username){

        // Get all highscores
        /* @var $repository UserRepository */
        $repository = $this->getDoctrine()->getRepository('MartonTopCarsBundle:User');
        $users = $repository-> findHighscores($sort, $username);

        foreach ($users as $user){
            $statisticsCalculator =  $this->get('statistics_calculator');
            $statisticsCalculator->init($user);
            /* @var $user User */
            $user->setStatistics($statisticsCalculator->getStatistics());
        }

        return $this->render('MartonTopCarsBundle:Default:Pages/leaderboard.html.twig', array(
            'users' => $users,
            'sort' => $sort,
            'username' => $username
        ));
    }

    // Handles submitted form for search
    public function searchAction(Request $request){

        $post_data = $request->request->all();
        $username = $post_data['username'];
        $sort = $post_data['sort'];

        return $this->redirect($this->generateUrl('marton_topcars_leaderboard', array('username' => $username, 'sort' => $sort)));
    }

    // Renders User profile page
    public function profileAction($user){

        // Get details of this user
        /* @var $repository UserRepository */
        $repository = $this->getDoctrine()->getRepository('MartonTopCarsBundle:User');
        $user_details = $repository-> findDetailsOfUser($user);

        $statisticsCalculator =  $this->get('statistics_calculator');
        $statisticsCalculator->init($user_details);
        $user_details->setStatistics($statisticsCalculator->getStatistics());

        $cars = $user_details->getCars();
        $cars_value = 0;
        foreach($cars as $car){
            $cars_value += $car->getPrice();
        }

        $all_users_ordered = $repository->findAllIdsOrderedBySkill();
        $rank = array_search($user_details->getId(), $all_users_ordered) +1;

        return $this->render('MartonTopCarsBundle:Default:Pages/user.html.twig', array(
            'user' => $user_details,
            'cars' => $cars,
            'cars_value' => $cars_value,
            'rank' => $rank
        ));
    }
} 