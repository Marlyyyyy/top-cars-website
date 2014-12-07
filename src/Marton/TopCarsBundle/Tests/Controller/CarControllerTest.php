<?php
/**
 * Created by PhpStorm.
 * User: Marci
 * Date: 03/12/14
 * Time: 19:42
 */

namespace Marton\TopCarsBundle\Tests\Controller;


use Marton\TopCarsBundle\Test\WebTestCase;

class CarControllerTest extends WebTestCase{

    private $client;

    /**
     * {@inheritDoc}
     */
    public function setUp()
    {
        $this->registerClient();
        $this->client = $this->loginClient();
    }

    // Test the Garage page
    public function testGarageAction(){

        $crawler = $this->client->request('GET', '/dealership');

        $this->assertGreaterThan(
            0,
            $crawler->filter('html:contains("nothing to buy :(")')->count()
        );
    }

    // Test both of the dealership pages
    public function testDealershipAction(){

        $crawler =  $this->client->request('GET', '/dealership');

        $this->assertGreaterThan(
            0,
            $crawler->filter('html:contains("nothing to buy :(")')->count()
        );

        $crawler =  $this->client->request('GET', '/dealership/all');

        $this->assertGreaterThan(
            0,
            $crawler->filter('html:contains("Alpina B7")')->count()
        );

    }

    // Test purchasing a car
    public function testPurchaseAction(){

        // Give the user some gold
        $user = $this->em->getRepository('MartonTopCarsBundle:User')->findDetailsOfUser("TestUser");

        $user_progress = $user->getProgress();
        $user_progress->setGold(10000);

        $this->em->flush();

        // Purchase a car
        $parameters = array("item" => 5);
        $this->client->request(
            'POST',
            '/car/purchase',
            $parameters,
            array(),
            array(
                'CONTENT_TYPE'          => 'application/json',
                'HTTP_REFERER'          => '/home',
                'HTTP_X-Requested-With' => 'XMLHttpRequest',
            )
        );

        $user_cars = $user->getCars();

        $this->assertEquals(1, count($user_cars));
    }

    // Test selecting a car
    public function testSelectAction(){

        // Select a car
        $parameters = array("item" => 5);
        $this->client->request(
            'POST',
            '/car/select',
            $parameters,
            array(),
            array(
                'CONTENT_TYPE'          => 'application/json',
                'HTTP_REFERER'          => '/garage',
                'HTTP_X-Requested-With' => 'XMLHttpRequest',
            )
        );

        $user = $this->em->getRepository('MartonTopCarsBundle:User')->findDetailsOfUser("TestUser");
        $selected_cars = $user->getSelectedCars();

        $this->assertEquals(1, count($selected_cars));
    }

    // Test unselecting all cars
    public function testUnselectAllAction(){

        // Unselect all
        $parameters = array("item" => 5);
        $this->client->request(
            'POST',
            '/car/unselect_all',
            $parameters,
            array(),
            array(
                'CONTENT_TYPE'          => 'application/json',
                'HTTP_REFERER'          => '/garage',
                'HTTP_X-Requested-With' => 'XMLHttpRequest',
            )
        );

        $user = $this->em->getRepository('MartonTopCarsBundle:User')->findDetailsOfUser("TestUser");
        $selected_cars = $user->getSelectedCars();

        $this->assertEquals(0, count($selected_cars));
    }

    /**
     * {@inheritDoc}
     */
    protected function tearDown()
    {
        $this->deleteClient($this->client);
    }
} 