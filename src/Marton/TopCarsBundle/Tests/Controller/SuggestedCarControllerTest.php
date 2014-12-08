<?php
/**
 * Created by PhpStorm.
 * User: Marci
 * Date: 04/12/14
 * Time: 01:16
 */

namespace Marton\TopCarsBundle\Tests\Controller;

use Marton\TopCarsBundle\Entity\SuggestedCar;
use Marton\TopCarsBundle\Entity\User;
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

        /* @var $user User */
        $user = $this->em->getRepository('MartonTopCarsBundle:User')->findDetailsOfUser("TestUser");
        
        $suggestedCar = new SuggestedCar();
        $suggestedCar->setModel("Test");
        $user->addSuggestedCar($suggestedCar);

        $this->em->flush();

        $suggestedCar = $this->em->getRepository('MartonTopCarsBundle:SuggestedCar')->findAll();

        $parameters = array("car_id" => $suggestedCar[0]->getId());

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
        $votedSuggestedCars = $user->getVotedSuggestedCars();

        $this->assertEquals(1, count($votedSuggestedCars));
    }

    // Test accepting a pending suggested car
    public function testAcceptAction(){

        /* @var $user User */
        $user = $this->em->getRepository('MartonTopCarsBundle:User')->findDetailsOfUser("TestUser");
        $suggestedCar = new SuggestedCar();
        $suggestedCar->setModel("Test");
        $user->addSuggestedCar($suggestedCar);

        $this->em->flush();

        $suggestedCar = $this->em->getRepository('MartonTopCarsBundle:SuggestedCar')->findAll();

        $parameters = array("car_id" => $suggestedCar[0]->getId());

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
        $responseContent = json_decode($response->getContent(), true);
        $errorMessages = $responseContent["error"];

        $this->assertEquals("Only administrators can accept pending cars!", $errorMessages[0][0]);
    }

    // Test deleting a pending suggested car, Test if the user's suggested cars are deleted after the user is deleted
    public function testDeleteAction(){

        /* @var $user User */
        $user = $this->em->getRepository('MartonTopCarsBundle:User')->findOneBy(array("username" => "TestUser"));
        $suggestedCar = new SuggestedCar();
        $suggestedCar->setModel("Test");
        $user->addSuggestedCar($suggestedCar);

        $this->em->flush();

        $suggestedCars = $user->getSuggestedCars();

        $parameters = array("car_id" => $suggestedCars[0]->getId());

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
        $responseContent = json_decode($response->getContent(), true);
        $errorMessages = $responseContent["error"];

        $this->assertEquals(array(), $errorMessages);
    }

    // Test querying a pending suggested car
    public function testQueryAction(){

        /* @var $user User */
        $user = $this->em->getRepository('MartonTopCarsBundle:User')->findOneBy(array("username" => "TestUser"));

        $suggestedCar = new SuggestedCar();
        $suggestedCar->setModel("Test");
        $user->addSuggestedCar($suggestedCar);

        $this->em->flush();

        $suggestedCars = $user->getSuggestedCars();

        $parameters = array("car_id" => $suggestedCars[0]->getId());

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
        $responseContent = json_decode($response->getContent(), true);
        $suggestedCar = $responseContent["car"];

        $this->assertEquals("Test", $suggestedCar["model"]);
    }

    /**
     * {@inheritDoc}
     */
    protected function tearDown()
    {
        $this->deleteClient($this->client);
    }
} 