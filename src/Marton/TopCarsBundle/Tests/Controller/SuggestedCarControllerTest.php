<?php
/**
 * Created by PhpStorm.
 * User: Marci
 * Date: 04/12/14
 * Time: 01:16
 */

namespace Marton\TopCarsBundle\Tests\Controller;

use Marton\TopCarsBundle\Entity\SuggestedCar;
use Marton\TopCarsBundle\Test\WebTestCase;

class SuggestedCarControllerTest extends WebTestCase{

    // Test rendering the Pending page
    public function testPendingAction(){

        $this->registerClient();
        $client = $this->loginClient();

        $crawler = $client->request('GET', '/pending');

        $this->assertGreaterThan(
            0,
            $crawler->filter('html:contains("Add")')->count()
        );
    }

    // Test voting on a pending suggested car
    public function testVoteAction(){

        $client = $this->loginClient();

        $em = $client->getContainer()->get('doctrine.orm.entity_manager');
        $user = $em->getRepository('MartonTopCarsBundle:User')->findDetailsOfUser("TestUser");
        $suggested_car = new SuggestedCar();
        $suggested_car->setModel("Test");
        $user->addSuggestedCar($suggested_car);

        $em->flush();

        $suggested_car = $em->getRepository('MartonTopCarsBundle:SuggestedCar')->findAll();

        $parameters = array("car_id" => $suggested_car[0]->getId());

        $client->request(
            'POST',
            '/pending/vote',
            $parameters,
            array(),
            array(
                'CONTENT_TYPE'          => 'application/json',
                'HTTP_REFERER'          => '/pending',
                'HTTP_X-Requested-With' => 'XMLHttpRequest',
            )
        );


        $user = $em->getRepository('MartonTopCarsBundle:User')->findDetailsOfUser("TestUser");
        $voted_suggested_cars = $user->getVotedSuggestedCars();

        $this->assertEquals(1, count($voted_suggested_cars));
    }

    // Test accepting a pending suggested car
    public function testAcceptAction(){

        $client = $this->loginClient();

        $em = $client->getContainer()->get('doctrine.orm.entity_manager');
        $user = $em->getRepository('MartonTopCarsBundle:User')->findDetailsOfUser("TestUser");
        $suggested_car = new SuggestedCar();
        $suggested_car->setModel("Test");
        $user->addSuggestedCar($suggested_car);

        $em->flush();

        $suggested_car = $em->getRepository('MartonTopCarsBundle:SuggestedCar')->findAll();

        $parameters = array("car_id" => $suggested_car[0]->getId());

        $client->request(
            'POST',
            '/pending/accept',
            $parameters,
            array(),
            array(
                'CONTENT_TYPE'          => 'application/json',
                'HTTP_REFERER'          => '/pending',
                'HTTP_X-Requested-With' => 'XMLHttpRequest',
            )
        );


        $response = $client->getResponse();

        // Check the error message
        $response_content = json_decode($response->getContent(), true);
        $error_messages = $response_content["error"];

        $this->assertEquals("Only administrators can accept pending cars", $error_messages[0][0]);

        $this->deleteClient($client);
    }

    // Test deleting a pending suggested car, Test if the user's suggested cars are deleted after the user is deleted
    public function testDeleteAction(){

        $this->registerClient();
        $client = $this->loginClient();

        $em = $client->getContainer()->get('doctrine.orm.entity_manager');
        $user = $em->getRepository('MartonTopCarsBundle:User')->findOneBy(array("username" => "TestUser"));
        $suggested_car = new SuggestedCar();
        $suggested_car->setModel("Test");
        $user->addSuggestedCar($suggested_car);

        $em->flush();

        $suggested_cars = $user->getSuggestedCars();

        $parameters = array("car_id" => $suggested_cars[0]->getId());

        $client->request(
            'POST',
            '/pending/delete',
            $parameters,
            array(),
            array(
                'CONTENT_TYPE'          => 'application/json',
                'HTTP_REFERER'          => '/pending',
                'HTTP_X-Requested-With' => 'XMLHttpRequest',
            )
        );

        $response = $client->getResponse();

        // Check the error message
        $response_content = json_decode($response->getContent(), true);
        $error_messages = $response_content["error"];

        $this->assertEquals(array(), $error_messages);
    }

    // Test querying a pending suggested car
    public function testQueryAction(){

        $client = $this->loginClient();

        $em = $client->getContainer()->get('doctrine.orm.entity_manager');
        $user = $em->getRepository('MartonTopCarsBundle:User')->findOneBy(array("username" => "TestUser"));

        $suggested_car = new SuggestedCar();
        $suggested_car->setModel("Test");
        $user->addSuggestedCar($suggested_car);

        $em->flush();

        $suggested_cars = $user->getSuggestedCars();

        $parameters = array("car_id" => $suggested_cars[0]->getId());

        $client->request(
            'POST',
            '/pending/query',
            $parameters,
            array(),
            array(
                'CONTENT_TYPE'          => 'application/json',
                'HTTP_REFERER'          => '/pending',
                'HTTP_X-Requested-With' => 'XMLHttpRequest',
            )
        );

        $response = $client->getResponse();

        // Check the model of the returned suggested car
        $response_content = json_decode($response->getContent(), true);
        $suggested_car = $response_content["car"];

        $this->assertEquals("Test", $suggested_car["model"]);

        $this->deleteClient($client);
    }
} 