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
use Marton\TopCarsBundle\Entity\Car;
use Marton\TopCarsBundle\Entity\SuggestedCar;
use Marton\TopCarsBundle\Entity\User;
use Marton\TopCarsBundle\Form\Type\SuggestedCarType;
use Marton\TopCarsBundle\Repository\SuggestedCarRepository;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

class SuggestedCarController extends Controller{

    // Renders the Prototypes page by fetching all suggested cars, tagging them with information (number of upvotes and
    // whether the user has the privilege to edit/delete/accept them) and rendering a form for editing.
    public function prototypesAction(){

        /* @var $repository SuggestedCarRepository */
        $repository = $this->getDoctrine()->getRepository('MartonTopCarsBundle:SuggestedCar');

        // Get all pending suggested cars together with their likes and creators
        $suggestedCars = $repository->selectAllSuggestedCars();

        /* @var $user User */
        $user = $this->get('security.context')->getToken()->getUser();

        // Load those pending cars' IDs which the logged in user has already up-voted
        $upVotedSuggestedCars = $repository->selectIdOfSuggestedCarsVotedByUserId($user->getId());
        $idOfUpVotedSuggestedCars = array();
        foreach ($upVotedSuggestedCars as $car){
            array_push($idOfUpVotedSuggestedCars, $car['id']);
        }

        // Tag suggested cars
        foreach($suggestedCars as &$car){
            
            // If the user has already upvoted the car
            if (in_array($car['id'], $idOfUpVotedSuggestedCars)){
                $car['upvoted'] = true;
            }else{
                $car['upvoted'] = false;
            }

            // If the car belongs to the user
            if ((int)$car['userId'] === $user->getId()){
                $car['belongs_to_user'] = true;
            }else{
                $car['belongs_to_user'] = false;
            }
        }

        // Check for admin permission
        if ($this->get('security.context')->isGranted('ROLE_ADMIN')) {
            $isAdmin = true;
        }else{
            $isAdmin = false;
        }

        // Create Form for editing pending suggested cars
        $suggestedCar = new SuggestedCar();
        $edit_form = $this->createForm(new SuggestedCarType(), $suggestedCar);

        return $this->render('MartonTopCarsBundle:Default:Pages/Subpages/prototypes.html.twig', array(
            'cars' => $suggestedCars,
            'edit_form' => $edit_form->createView(),
            'is_admin' => $isAdmin
        ));
    }

    // Handles Ajax POST request to up-vote a suggested car. It checkes whether the user has already up-voted the car,
    // in which case it counts as removing her vote.
    public function voteAction(Request $request){

        /* @var $user User */
        $user= $this->get('security.context')->getToken()->getUser();
        $progress = $user->getProgress();

        // Get the car to be upvoted
        $carId = $request->request->get('car_id');
        $em = $this->getDoctrine()->getManager();
        $car = $em->getRepository('MartonTopCarsBundle:SuggestedCar')->findOneById(array($carId));

        /* @var $carsUpVotedByUser ArrayCollection */
        $carsUpVotedByUser = $user->getVotedSuggestedCars();

        // Check if user has already voted
        if ($carsUpVotedByUser->contains($car)){
            $user->removeVotedSuggestedCars($car);
            $action = "removed";
        }else{
            $user->addVotedSuggestedCars($car);
            $action = "added";
        }

        $em->flush();

        return new JsonResponse(array('result' => $action));
    }

    // Handles Ajax POST request to accept a suggested car. Only the admins have the privilege to execute this action.
    // A new car is created with the details of the accepted suggested car, then the suggested car gets removed. The
    // newly created car is rewarded to the user who suggested it, and the car's image is moved to its final directory.
    public function acceptAction(Request $request){

        $error = array();

        // Check if the user is an admin
        if (!$this->get('security.context')->isGranted('ROLE_ADMIN')) {

            array_push($error, array("Only administrators can accept pending cars!"));
            return new JsonResponse(array('error' => $error));
        }

        // Get the car to be accepted
        $carId = $request->request->get('car_id');
        /* @var $suggestedCar SuggestedCar */
        $em = $this->getDoctrine()->getManager();
        $suggestedCar = $em->getRepository('MartonTopCarsBundle:SuggestedCar')->findOneById(array($carId));

        // Check if there exists a car with the given id
        if(sizeof($suggestedCar) == 0){

            array_push($error, array("Such car does not exist!"));
            return new JsonResponse(array('error' => $error));
        }

        // Move image to the final directory
        if ($suggestedCar->getImage() !== "default.png"){

            $oldPath = $this->get('kernel')->getRootDir() . '/../web/bundles/martontopcars/images/card_game_suggest/'.$suggestedCar->getImage();
            $imageFile = new File($oldPath);
            $newPath = $this->get('kernel')->getRootDir() . '/../web/bundles/martontopcars/images/card_game';
            $imageFile->move($newPath, $suggestedCar->getImage());
            $imageFile = null;
        }else{

            array_push($error, array("This car doesn't have its own image! Please add one :)"));
            return new JsonResponse(array('error' => $error));
        }

        // Create a new car entity as a copy of the suggested car
        $car = new Car();
        $car->setModel($suggestedCar->getModel());
        $car->setImage($suggestedCar->getImage());
        $car->setSpeed($suggestedCar->getSpeed());
        $car->setPower($suggestedCar->getPower());
        $car->setTorque($suggestedCar->getTorque());
        $car->setAcceleration($suggestedCar->getAcceleration());
        $car->setWeight($suggestedCar->getWeight());

        /* @var $priceCalculator PriceCalculator */
        $priceCalculator = $this->get('price_calculator');
        $car->setPrice($priceCalculator->calculatePrice($car));

        // Give the car to its suggestor as a present
        /* @var $owner User */
        $owner = $suggestedCar->getUser();
        $owner->addCar($car);

        try{

            $em->persist($car);
            $em->remove($suggestedCar);
            $em->flush();
        }catch(Exception $e){

            array_push($error, "Persisting to the database failed :(");
        }

        return new JsonResponse(array('error' => $error));
    }

    // Handles Ajax POST request to delete a suggested car. Only the admins and the owner of the suggested car have the
    // privilege to execute this action. The image of the car also gets removed.
    public function deleteAction(Request $request){

        $error = array();

        // Get car
        $carId = $request->request->get('car_id');
        /* @var $suggestedCar SuggestedCar */
        $em = $this->getDoctrine()->getManager();
        $suggestedCar = $em->getRepository('MartonTopCarsBundle:SuggestedCar')->findOneById(array($carId));

        // Check if there exists a car with the given id
        if(sizeof($suggestedCar) == 0){

            array_push($error, array("Such car does not exist!"));
            return new JsonResponse(array('error' => $error));
        }

        /* @var $user User */
        $user = $this->get('security.context')->getToken()->getUser();

        // Check if the user is an admin OR the owner of the car
        if ( (!$this->get('security.context')->isGranted('ROLE_ADMIN')) and ($user !== $suggestedCar->getUser())){

            array_push($error, array("You must be the owner of the car in order to delete that!"));
            return new JsonResponse(array('error' => $error));
        }

        // Remove image as long as it's not the default one
        if ($suggestedCar->getImage() !== "default.png"){

            $oldPath = $this->get('kernel')->getRootDir() . '/../web/bundles/martontopcars/images/card_game_suggest/'.$suggestedCar->getImage();

            $fileHelper = $this->get('file_helper');
            $fileHelper->removeFile($oldPath);

            $imageFile = null;
        }

        $em->remove($suggestedCar);
        $em->flush();

        return new JsonResponse(array('error' => $error));
    }

    // Handles Ajax POST request to edit a suggested car as long as it belongs to the user or the user is an admin.
    // When creating a new car, a default image is set in case the user hasn't uploaded one.
    // When editing an existing car, a newly uploaded image overwrites the previous one unless it was the default one.
    public function editOrCreateAction(Request $request){

        /* @var $user User */
        $user = $this->get('security.context')->getToken()->getUser();

        // Get the ID of the car to be edited
        $carId = (int) $request->request->get('car_id');

        $error = array();

        $em = $this->getDoctrine()->getManager();
        
        // Check if it's a new car or existing car (-1 stands for new car)
        if($carId === -1){

            $suggestedCar = new SuggestedCar();
            $user->addSuggestedCar($suggestedCar);
            $suggestedCarDefaultImage = "default.png";

        }else{

            // Get suggested car to be edited
            /* @var $suggestedCar SuggestedCar*/
            $suggestedCar = $em->getRepository('MartonTopCarsBundle:SuggestedCar')->findOneById(array($carId));

            // Check if there exists a car with the given id
            if(sizeof($suggestedCar) == 0){

                array_push($error, array("Such car does not exist!"));
                return new JsonResponse(array('error' => $error));
            }

            // Check if the car to be edited is indeed the user's car OR that the user is an admin
            if (!in_array($suggestedCar,$user->getSuggestedCars()) and (!$this->get('security.context')->isGranted('ROLE_ADMIN'))){

                array_push($error, array("You must be the owner of the car in order to edit that!"));
                return new JsonResponse(array('error' => $error));
            }

            // Save the car's previous picture's path
            $suggestedCarDefaultImage = $suggestedCar->getImage();
            $suggestedCar->setImage(null);
        }

        $form = $this->createForm(new SuggestedCarType(), $suggestedCar);
        $form->submit($request);
        $suggestedCar = $form->getData();

        if ($form->isValid()){

            $fileHelper = $this->get('file_helper');

            $imageFile = $suggestedCar->getImageFile();

            // Check if the user has uploaded any image
            if($imageFile != null){

                $imageDirPath = $this->get('kernel')->getRootDir() . '/../web/bundles/martontopcars/images/card_game_suggest/';

                // Remove previous image unless it's the default one
                if ($suggestedCarDefaultImage !== 'default.png'){

                    $oldPath = $imageDirPath . $suggestedCarDefaultImage;
                    $fileHelper->removeFile($oldPath);
                }

                // Move the new image with a new unique filename to the correct directory
                $file_name = $fileHelper->makeUniqueName($user->getId(), $imageFile->getClientOriginalName());
                $imageFile->move($imageDirPath, $file_name);
                $suggestedCar->setImage($file_name);

            }else{

                $suggestedCar->setImage($suggestedCarDefaultImage);
            }

            $imageFile = null;

            $em->flush();

            // Add flash notice
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
            return new JsonResponse(array('error' => $form_errors));
        }

        return new JsonResponse(array('error' => $error));
    }

    // Handles Ajax POST request to return the details of a suggested car that is about to be edited.
    public function queryAction(Request $request){

        $carId = $request->request->get('car_id');

        /* @var $suggestedCar SuggestedCar */
        $em = $this->getDoctrine()->getManager();
        $suggestedCar = $em->getRepository('MartonTopCarsBundle:SuggestedCar')->findOneById(array($carId));

        return new JsonResponse(array('car' => $suggestedCar));
    }

    // Helper method to return all error messages within a submitted form
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
} 