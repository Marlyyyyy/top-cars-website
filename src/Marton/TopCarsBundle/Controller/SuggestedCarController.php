<?php
/**
 * Created by PhpStorm.
 * User: Marci
 * Date: 08/11/14
 * Time: 14:03
 */

namespace Marton\TopCarsBundle\Controller;


use Doctrine\Common\Collections\ArrayCollection;
use Exception;
use Marton\TopCarsBundle\Classes\PriceCalculator;
use Marton\TopCarsBundle\Entity\Car;
use Marton\TopCarsBundle\Entity\SuggestedCar;
use Marton\TopCarsBundle\Entity\User;
use Marton\TopCarsBundle\Form\Type\SuggestedCarType;
use Marton\TopCarsBundle\Repository\SuggestedCarRepository;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SuggestedCarController extends Controller{

    // Render page for suggesting new cars
    public function suggestAction(){

        $suggested_car = new SuggestedCar();

        $form = $this->createForm(new SuggestedCarType(), $suggested_car, array(
                'action' => $this->generateUrl('marton_topcars_create_suggestedCar'))
        );

        return $this->render('MartonTopCarsBundle:Default:Pages/suggest.html.twig', array(
            'form' => $form->createView()
        ));
    }

    // Render page for displaying pending suggested cars
    public function pendingAction(){

        // Get all suggested cars
        /* @var $repository SuggestedCarRepository */
        $repository = $this->getDoctrine()->getRepository('MartonTopCarsBundle:SuggestedCar');

        // Get all pending suggested cars together with their likes and creators
        $suggested_cars = $repository->selectAllSuggestedCars();

        // Get the user
        /* @var $user User */
        $user = $this->get('security.context')->getToken()->getUser();

        // Load those pending cars' IDs which the logged in user has already voted up
        $liked_suggested_cars = $repository->selectIdOfSuggestedCarsVotedByUserId($user->getId());
        $id_of_liked_suggested_cars = array();
        foreach ($liked_suggested_cars as $car){
            array_push($id_of_liked_suggested_cars, $car['id']);
        }

        // Tag suggested cars in terms of whether the logged in user has voted on it or not.
        foreach($suggested_cars as &$car){
            if (in_array($car['id'], $id_of_liked_suggested_cars)){
                $car['upvoted'] = true;
            }else{
                $car['upvoted'] = false;
            }
        }

        // Create Form for editing pending suggested cars
        $suggested_car = new SuggestedCar();
        $edit_form = $this->createForm(new SuggestedCarType(), $suggested_car);

        return $this->render('MartonTopCarsBundle:Default:Pages/Subpages/pending.html.twig', array(
            'cars' => $suggested_cars,
            'edit_form' => $edit_form->createView()
        ));
    }

    // Ajax call for voting
    public function voteAction(Request $request){

        $em = $this->getDoctrine()->getManager();

        // Get user entity
        /* @var $user User */
        $user= $this->get('security.context')->getToken()->getUser();
        $progress = $user->getProgress();

        // Get car
        $car_id = $request->request->get('car_id');
        $car = $em->getRepository('MartonTopCarsBundle:SuggestedCar')->findOneById(array($car_id));

        /* @var $user_voted_cars ArrayCollection */
        $user_voted_cars = $user->getVotedSuggestedCars();

        // Check if user has already voted
        if ($user_voted_cars->contains($car)){
            $user->removeVotedSuggestedCars($car);
            $response_msg = "removed";
        }else{
            $user->addVotedSuggestedCars($car);
            $response_msg = "added";
        }

        $em->flush();

        $response = new Response(json_encode(array(
            'result' => $response_msg)));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    // Ajax call for accepting
    public function acceptAction(Request $request){
        // TODO: add security check - only allow this action for admins

        $em = $this->getDoctrine()->getManager();

        $error = array();

        // Get car
        $car_id = $request->request->get('car_id');
        /* @var $suggestedCar SuggestedCar */
        $suggestedCar = $em->getRepository('MartonTopCarsBundle:SuggestedCar')->findOneById(array($car_id));

        // Check if there exists a car with the given id
        if(sizeof($suggestedCar) == 0){

            array_push($error, array("id", "Such car does not exist! <br>"));
            $response = new Response(json_encode(array(
                'error' => $error)));
            $response->headers->set('Content-Type', 'application/json');

            return $response;
        }

        // Move image to the final directory
        if ($suggestedCar->getImage() !== "default.png"){

            $old_path = $this->get('kernel')->getRootDir() . '/../web/bundles/martontopcars/images/card_game_suggest/'.$suggestedCar->getImage();
            $image_file = new File($old_path);
            $new_path = $this->get('kernel')->getRootDir() . '/../web/bundles/martontopcars/images/card_game';
            $image_file->move($new_path, $suggestedCar->getImage());
            $image_file = null;

        }else{

            array_push($error, array("image", "This car doesn't have its own image! <br>"));
            $response = new Response(json_encode(array(
                'error' => $error)));
            $response->headers->set('Content-Type', 'application/json');

            return $response;
        }


        // Create car entity as a copy of the suggested car
        $car = new Car();
        $car->setModel($suggestedCar->getModel());
        $car->setImage($suggestedCar->getImage());
        $car->setSpeed($suggestedCar->getSpeed());
        $car->setPower($suggestedCar->getPower());
        $car->setTorque($suggestedCar->getTorque());
        $car->setAcceleration($suggestedCar->getAcceleration());
        $car->setWeight($suggestedCar->getWeight());

        $priceCalculator = new PriceCalculator();
        $car->setPrice($priceCalculator->calculatePrice($car));
        try{
            $em->persist($car);
            $em->remove($suggestedCar);
            $em->flush();

            $response_msg = "success";
        }catch(Exception $e){
            $response_msg = "fail";
        }

        $response = new Response(json_encode(array(
            'result' => $response_msg)));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    // Handling form before creating a SuggestedCar
    public function createAction(Request $request){

        $suggested_car = new SuggestedCar();

        $form = $this->createForm(new SuggestedCarType(), $suggested_car, array(
                'action' => $this->generateUrl('marton_topcars_create_suggestedCar'))
        );

        $form->handleRequest($request);

        if ($form->isValid()){

            // Get image file and move it to designated directory
            $image_file = $suggested_car->getImage();


            // Check if the user has uploaded any image
            if($image_file != null){

                $new_path = $this->get('kernel')->getRootDir() . '/../web/bundles/martontopcars/images/card_game_suggest';
                $image_file->move($new_path, $image_file->getClientOriginalName());

                $suggested_car->setImage($image_file->getClientOriginalName());
            }else{
                $suggested_car->setImage("default.png");
            }

            $image_file = null;

            // Get the user and associate their newly suggested car with them
            /* @var $user User */
            $user = $this->get('security.context')->getToken()->getUser();

            $user->addSuggestedCar($suggested_car);
            $suggested_car->setUser($user);

            $em = $this->getDoctrine()->getManager();
            $em->flush();

            return $this->redirect($this->generateUrl('marton_topcars_default'));

        }else{

            // TODO: Display errors
            return $this->render('MartonTopCarsBundle:Default:Pages/suggest.html.twig', array(
                'form' => $form->createView()
            ));
        }
    }

    // Ajax call for editing
    public function editOrCreateAction(Request $request){

        $em = $this->getDoctrine()->getManager();

        // Get user entity
        /* @var $user User */
        $user= $this->get('security.context')->getToken()->getUser();

        // Get id of the car to be edited
        $car_id = $request->request->get('car_id');

        $car_id = (int) $car_id;

        $error = array();

        // Check if it's a new car or existing car
        if($car_id === -1){

            // Create new suggested car
            $suggested_car = new SuggestedCar();

            $suggested_car->setUser($user);

            $suggested_default_image = "default.png";

        }else{

            // Get suggested car to be edited
            /* @var $suggested_car SuggestedCar*/
            $suggested_car = $em->getRepository('MartonTopCarsBundle:SuggestedCar')->findOneById(array($car_id));

            // Check if there exists a car with the given id
            if(sizeof($suggested_car) == 0){

                array_push($error, array("id", "Such car does not exist! <br>"));
                $response = new Response(json_encode(array(
                    'error' => $error)));
                $response->headers->set('Content-Type', 'application/json');

                return $response;
            }

            // Check if the car to be edited is indeed the user's car
            if (!in_array($suggested_car,$user->getSuggestedCars())){

                array_push($error, array("id", "This is not your suggested car! <br>"));
                $response = new Response(json_encode(array(
                    'error' => $error)));
                $response->headers->set('Content-Type', 'application/json');

                return $response;
            }

            // Save the car's previous picture's path
            $suggested_default_image = $suggested_car->getImage();
            $suggested_car->setImage(null);

        }


        $form = $this->createForm(new SuggestedCarType(), $suggested_car);

        $form->submit($request);

        $suggested_car = $form->getData();

        // TODO: Form validation
        if ($form->isValid()){

            $image_file = $suggested_car->getImage();

            // Check if the user has uploaded any image
            if($image_file != null){

                $new_path = $this->get('kernel')->getRootDir() . '/../web/bundles/martontopcars/images/card_game_suggest';
                $image_file->move($new_path, $image_file->getClientOriginalName());

                $suggested_car->setImage($image_file->getClientOriginalName());
            }else{

                $suggested_car->setImage($suggested_default_image);
            }

            $image_file = null;

            // If it's a new car, then add it to the database
            if($car_id === -1){
                $em->persist($suggested_car);
            }
            $em->flush();

            $this->get('session')->getFlashBag()->add(
                'notice',
                'Your changes were saved!'
            );

        }else{
            array_push($error, array("form", "Form errors..! <br>"));
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

    // Ajax call for returning details of a pending suggested car to be edited
    public function queryAction(Request $request){

        $em = $this->getDoctrine()->getManager();

        // Get car
        $car_id = $request->request->get('carId');
        /* @var $suggestedCar SuggestedCar */
        $suggestedCar = $em->getRepository('MartonTopCarsBundle:SuggestedCar')->findOneById(array($car_id));

        $response = new Response(json_encode(array(
            'car' => $suggestedCar)));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }
} 