<?php
/**
 * Created by PhpStorm.
 * User: Marci
 * Date: 03/12/14
 * Time: 23:27
 */

namespace Marton\TopCarsBundle\Tests\Controller;

use Marton\TopCarsBundle\Entity\User;
use Marton\TopCarsBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

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

        /* @var $response Response */
        $response = $this->client->getResponse();

        $responseContent = json_decode($response->getContent(), true);

        $this->assertEquals("up", $responseContent["levelChange"]);
    }

    // Test checking for Free For All
    public function testCheckFreeForAllAction(){

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

        /* @var $response Response */
        $response = $this->client->getResponse();

        // Check the number of cards returned
        $responseContent = json_decode($response->getContent(), true);
        $deck = json_decode($responseContent["deck"]);

        $this->assertGreaterThan(0, count($deck));
    }

    // Test checking for Classic
    public function testCheckClassicAction(){

        // Give the user 10 cars
        /* @var $user User */
        $user = $this->em->getRepository('MartonTopCarsBundle:User')->findDetailsOfUser("TestUser");
        $carRepository = $this->em->getRepository('MartonTopCarsBundle:Car');

        for ($i=0;$i<10;$i++){
            $user->addSelectedCars($carRepository->findOneById($i+1));
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

        /* @var $response Response */
        $response = $this->client->getResponse();

        // Check the number of cards returned
        $responseContent = json_decode($response->getContent(), true);
        $selectedCars = json_decode($responseContent["selected_cars"]);

        $this->assertEquals(10, count($selectedCars));
    }

    /**
     * {@inheritDoc}
     */
    protected function tearDown()
    {
        $this->deleteClient($this->client);
    }
} 