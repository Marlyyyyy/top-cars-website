<?php
/**
 * Created by PhpStorm.
 * User: Marci
 * Date: 27/10/14
 * Time: 22:01
 */

namespace Marton\TopCarsBundle\Controller;

use Marton\TopCarsBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class PageController extends Controller {

    // Home page
    public function homeAction(){

        return $this->render('MartonTopCarsBundle:Default:Pages/home.html.twig');
    }

    // Cars -> Dealership page
    public function dealershipAction(){

        return $this->render('MartonTopCarsBundle:Default:Pages/Subpages/dealership.html.twig');
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