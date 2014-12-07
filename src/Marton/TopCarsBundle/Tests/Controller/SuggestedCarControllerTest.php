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

    private $client;

    /**
     * {@inheritDoc}
     */
    public function setUp()
    {
        $this->registerClient();
        $this->client = $this->loginClient();
    }

    // Test rendering the Pending page
    public function testPendingAction(){

        $crawler = $this->client->request('GET', '/prototypes');

        $this->assertGreaterThan(
            0,
            $crawler->filter('html:contains("Add")')->count()
        );
    }

    // Test voting on a pending suggested car
    public function testVoteAction(){

        $user = $this->em->getRepository('MartonTopCarsBundle:User')->findDetailsOfUser("TestUser");
        $suggested_car = new SuggestedCar();
        $suggested_car->setModel("Test");
        $user->addSuggestedCar($suggested_car);

        $this->em->flush();

        $suggested_car = $this->em->getRepository('MartonTopCarsBundle:SuggestedCar')->findAll();

        $parameters = array("car_id" => $suggested_car[0]->getId());

        $this->client->request(
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


        $user = $this->em->getRepository('MartonTopCarsBundle:User')->findDetailsOfUser("TestUser");
        $voted_suggested_cars = $user->getVotedSuggestedCars();

        $this->assertEquals(1, count($voted_suggested_cars));
    }

    // Test accepting a pending suggested car
    public function testAcceptAction(){

        $user = $this->em->getRepository('MartonTopCarsBundle:User')->findDetailsOfUser("TestUser");
        $suggested_car = new SuggestedCar();
        $suggested_car->setModel("Test");
        $user->addSuggestedCar($suggested_car);

        $this->em->flush();

        $suggested_car = $this->em->getRepository('MartonTopCarsBundle:SuggestedCar')->findAll();

        $parameters = array("car_id" => $suggested_car[0]->getId());

        $this->client->request(
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


        $response = $this->client->getResponse();

        // Check the error message
        $response_content = json_decode($response->getContent(), true);
        $error_messages = $response_content["error"];

        $this->assertEquals("Only administrators can accept pending cars", $error_messages[0][0]);
    }

    // Test deleting a pending suggested car, Test if the user's suggested cars are deleted after the user is deleted
    public function testDeleteAction(){

        $user = $this->em->getRepository('MartonTopCarsBundle:User')->findOneBy(array("username" => "TestUser"));
        $suggested_car = new SuggestedCar();
        $suggested_car->setModel("Test");
        $user->addSuggestedCar($suggested_car);

        $this->em->flush();

        $suggested_cars = $user->getSuggestedCars();

        $parameters = array("car_id" => $suggested_cars[0]->getId());

        $this->client->request(
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

        $response = $this->client->getResponse();

        // Check the error message
        $response_content = json_decode($response->getContent(), true);
        $error_messages = $response_content["error"];

        $this->assertEquals(array(), $error_messages);
    }

    // Test querying a pending suggested car
    public function testQueryAction(){

        $user = $this->em->getRepository('MartonTopCarsBundle:User')->findOneBy(array("username" => "TestUser"));

        $suggested_car = new SuggestedCar();
        $suggested_car->setModel("Test");
        $user->addSuggestedCar($suggested_car);

        $this->em->flush();

        $suggested_cars = $user->getSuggestedCars();

        $parameters = array("car_id" => $suggested_cars[0]->getId());

        $this->client->request(
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

        $response = $this->client->getResponse();

        // Check the model of the returned suggested car
        $response_content = json_decode($response->getContent(), true);
        $suggested_car = $response_content["car"];

        $this->assertEquals("Test", $suggested_car["model"]);
    }

    /**
     * {@inheritDoc}
     */
    protected function tearDown()
    {
        $this->deleteClient($this->client);
    }
} 