<?php
/**
 * Created by PhpStorm.
 * User: Marci
 * Date: 27/10/14
 * Time: 22:01
 */

namespace Marton\TopCarsBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class PageController extends Controller {

    // Home page
    public function homeAction(){

        return $this->render('MartonTopCarsBundle:Default:Pages/home.html.twig');
    }

} 