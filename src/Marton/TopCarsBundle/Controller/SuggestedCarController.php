<?php
/**
 * Created by PhpStorm.
 * User: Marci
 * Date: 08/11/14
 * Time: 14:03
 */

namespace Marton\TopCarsBundle\Controller;


use Marton\TopCarsBundle\Entity\SuggestedCar;
use Marton\TopCarsBundle\Form\Type\SuggestedCarType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

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

            $new_path = $this->get('kernel')->getRootDir() . '/../web/bundles/martontopcars/images/card_game_suggest';
            $image_file->move($new_path, $image_file->getClientOriginalName());

            $suggested_car->setImage($image_file->getClientOriginalName());

            $image_file = null;

            // Get the user
            /* @var $user User */
            $user = $this->get('security.context')->getToken()->getUser();

            $user->addSuggestedCar($suggested_car);
            $suggested_car->setUser($user);

            $em = $this->getDoctrine()->getManager();
            $em->flush();

            return $this->redirect($this->generateUrl('marton_topcars_default'));
        }else{
            return $this->render('MartonTopCarsBundle:Default:Pages/suggest.html.twig', array(
                'form' => $form->createView()
            ));
        }
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

        return $this->render('MartonTopCarsBundle:Default:Pages/pending.html.twig', array(
            'cars' => $suggested_cars
        ));
    }
} 