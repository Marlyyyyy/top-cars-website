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
use Marton\TopCarsBundle\Classes\FileHelper;
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

        // Tag suggested cars: has the user voted on it? does the car belong to the user?
        foreach($suggested_cars as &$car){
            if (in_array($car['id'], $id_of_liked_suggested_cars)){
                $car['upvoted'] = true;
            }else{
                $car['upvoted'] = false;
            }

            if ((int)$car['userId'] === $user->getId()){
                $car['belongs_to_user'] = true;
            }else{
                $car['belongs_to_user'] = false;
            }
        }

        // Check for admin permission
        if ($this->get('security.context')->isGranted('ROLE_ADMIN')) {
            $is_admin = true;
        }else{
            $is_admin = false;
        }

        // Create Form for editing pending suggested cars
        $suggested_car = new SuggestedCar();
        $edit_form = $this->createForm(new SuggestedCarType(), $suggested_car);

        return $this->render('MartonTopCarsBundle:Default:Pages/Subpages/pending.html.twig', array(
            'cars' => $suggested_cars,
            'edit_form' => $edit_form->createView(),
            'is_admin' => $is_admin
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

        $em = $this->getDoctrine()->getManager();

        $error = array();

        // Get car
        $car_id = $request->request->get('car_id');
        /* @var $suggestedCar SuggestedCar */
        $suggestedCar = $em->getRepository('MartonTopCarsBundle:SuggestedCar')->findOneById(array($car_id));

        // Check if there exists a car with the given id
        if(sizeof($suggestedCar) == 0){

            array_push($error, array("Such car does not exist!"));
            $response = new Response(json_encode(array(
                'error' => $error)));
            $response->headers->set('Content-Type', 'application/json');

            return $response;
        }

        // Check if the user is an admin
        if (!$this->get('security.context')->isGranted('ROLE_ADMIN')) {
            array_push($error, array("Only administrators can accept pending cars"));
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

            array_push($error, array("This car doesn't have its own image!"));
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

    // Ajax call for deleting
    public function deleteAction(Request $request){

        $em = $this->getDoctrine()->getManager();

        $error = array();

        // Get car
        $car_id = $request->request->get('car_id');
        /* @var $suggestedCar SuggestedCar */
        $suggestedCar = $em->getRepository('MartonTopCarsBundle:SuggestedCar')->findOneById(array($car_id));

        // Check if there exists a car with the given id
        if(sizeof($suggestedCar) == 0){

            array_push($error, array("Such car does not exist!"));
            $response = new Response(json_encode(array(
                'error' => $error)));
            $response->headers->set('Content-Type', 'application/json');

            return $response;
        }

        // Get user entity
        /* @var $user User */
        $user= $this->get('security.context')->getToken()->getUser();

        // Check if the user is an admin OR the owner of the car
        if ( (!$this->get('security.context')->isGranted('ROLE_ADMIN')) and ($user !== $suggestedCar->getUser())){
            array_push($error, array("You must be the owner of the car in order to delete that"));
            $response = new Response(json_encode(array(
                'error' => $error)));
            $response->headers->set('Content-Type', 'application/json');

            return $response;
        }


        // Remove image as long as it's not the default one
        if ($suggestedCar->getImage() !== "default.png"){

            $old_path = $this->get('kernel')->getRootDir() . '/../web/bundles/martontopcars/images/card_game_suggest/'.$suggestedCar->getImage();

            if (file_exists($old_path)){

                $image_file = new File($old_path);

                if (is_writable($image_file)){

                    unlink($image_file);
                }else{

                    array_push($error, "You do not have permission to remove files");
                }
            }else{
                array_push($error, "Such file does not exist");
            }

            $image_file = null;
        }


       $em->remove($suggestedCar);
       $em->flush();

        $response = new Response(json_encode(array(
            'error' => $error)));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
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

        // Check if it's a new car or existing car (-1 stands for new car)
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

                array_push($error, array("Such car does not exist!"));
                $response = new Response(json_encode(array(
                    'error' => $error)));
                $response->headers->set('Content-Type', 'application/json');

                return $response;
            }

            // Check if the car to be edited is indeed the user's car OR that the user is an admin
            if (!in_array($suggested_car,$user->getSuggestedCars()) and (!$this->get('security.context')->isGranted('ROLE_ADMIN'))){

                array_push($error, array("This is not your suggested car!"));
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

        if ($form->isValid()){

            $image_file = $suggested_car->getImageFile();

            // Check if the user has uploaded any image
            if($image_file != null){

                $image_dir_path = $this->get('kernel')->getRootDir() . '/../web/bundles/martontopcars/images/card_game_suggest/';

                // Remove previous image
                if ($suggested_default_image !== 'default.png'){

                    $old_path = $image_dir_path.$suggested_default_image;

                    // TODO: factor out this check
                    if (file_exists($old_path)){

                        $old_image_file = new File($old_path);

                        if (is_writable($old_image_file)){

                            unlink($old_image_file);
                        }
                    }
                }

                $file_helper = new FileHelper();
                $file_name = $file_helper->makeUniqueName($user->getId(), $image_file->getClientOriginalName());

                $image_file->move($image_dir_path, $file_name);

                $suggested_car->setImage($file_name);
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

            // Handling form errors
            // Make sure "extension=php_fileinfo.dll" is enabled in your php.ini. This allows checking mimetypes.
            $form_errors = $this->getErrorMessages($form);
            foreach ($form_errors as $form_error){
                array_push($error, $form_error);
            }
            $response = new Response(json_encode(array(
                'error' => $form_errors)));
            $response->headers->set('Content-Type', 'application/json');

            return $response;
        }



        $response = new Response(json_encode(array(
            'error' => $error)));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    private function getErrorMessages(\Symfony\Component\Form\Form $form) {
        $errors = array();

        // Single error message
        foreach ($form->getErrors() as $key => $error) {
            $errors[] = $error->getMessage();
        }

        // Array of error messages
        foreach ($form->all() as $child) {
            if (!$child->isValid()) {
                $errors[$child->getName()] = $this->getErrorMessages($child);
            }
        }

        return $errors;
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