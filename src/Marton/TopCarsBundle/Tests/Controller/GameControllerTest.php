<?php
/**
 * Created by PhpStorm.
 * User: Marci
 * Date: 03/12/14
 * Time: 23:27
 */

namespace Marton\TopCarsBundle\Tests\Controller;

use Marton\TopCarsBundle\Test\WebTestCase;

class GameControllerTest extends WebTestCase{

    private $client;

    /**
     * {@inheritDoc}
     */
    public function setUp()
    {
        $this->registerClient();
        $this->client = $this->loginClient();
    }

    // Test rendering the Game page
    public function testGameAction(){

        $crawler = $this->client->request('GET', '/game');

        $this->assertGreaterThan(
            0,
            $crawler->filter('html:contains("Settings")')->count()
        );
    }

    // Test updating the user's score
    public function testPostUserScoreAction(){

        $user = $this->em->getRepository('MartonTopCarsBundle:User')->findDetailsOfUser("TestUser");

        // Post a round result
        $parameters = array("score" => 50000, "streak" => 10, "roundResult" => "win");
        $this->client->request(
            'POST',
            '/game/post_score',
            $parameters,
            array(),
            array(
                'CONTENT_TYPE'          => 'application/json',
                'HTTP_REFERER'          => '/game',
                'HTTP_X-Requested-With' => 'XMLHttpRequest',
            )
        );

        $response = $this->client->getResponse();

        $response_content = json_decode($response->getContent(), true);

        $this->assertEquals("up", $response_content["levelChange"]);
    }

    // Test checking for Free For All
    public function testCheckFreeForAllAction(){

        $user = $this->em->getRepository('MartonTopCarsBundle:User')->findDetailsOfUser("TestUser");

        $this->client->request(
            'POST',
            '/game/check/free_for_all',
            array(),
            array(),
            array(
                'CONTENT_TYPE'          => 'application/json',
                'HTTP_REFERER'          => '/game',
                'HTTP_X-Requested-With' => 'XMLHttpRequest',
            )
        );

        $response = $this->client->getResponse();

        // Check the number of cards returned
        $response_content = json_decode($response->getContent(), true);
        $deck = json_decode($response_content["deck"]);

        $this->assertGreaterThan(0, count($deck));
    }

    // Test checking for Classic
    public function testCheckClassicAction(){

        // Give the user 10 cars
        $user = $this->em->getRepository('MartonTopCarsBundle:User')->findDetailsOfUser("TestUser");
        $car_repository = $this->em->getRepository('MartonTopCarsBundle:Car');

        for ($i=0;$i<10;$i++){
            $user->addSelectedCars($car_repository->findOneById($i+1));
        }

        $this->em->flush();

        $this->client->request(
            'POST',
            '/game/check/classic',
            array(),
            array(),
            array(
                'CONTENT_TYPE'          => 'application/json',
                'HTTP_REFERER'          => '/game',
                'HTTP_X-Requested-With' => 'XMLHttpRequest',
            )
        );

        $response = $this->client->getResponse();

        // Check the number of cards returned
        $response_content = json_decode($response->getContent(), true);
        $selected_cars = json_decode($response_content["selected_cars"]);

        $this->assertEquals(10, count($selected_cars));
    }

    /**
     * {@inheritDoc}
     */
    protected function tearDown()
    {
        $this->deleteClient($this->client);
    }
} 