<?php
/**
 * Created by PhpStorm.
 * User: Marci
 * Date: 07/12/14
 * Time: 18:23
 */

namespace Marton\TopCarsBundle\Tests\Controller;


use Marton\TopCarsBundle\Test\WebTestCase;

class PageControllerTest extends WebTestCase{

    public function testHomeAction(){

        $client = static::createClient();
        $client->followRedirects();
        $crawler = $client->request('GET', '/');

        $this->assertGreaterThan(
            0,
            $crawler->filter('html:contains("game")')->count()
        );
    }

    public function testAboutAction(){

        $client = static::createClient();
        $crawler = $client->request('GET', '/about');

        $this->assertGreaterThan(
            0,
            $crawler->filter('html:contains("Hello visitor!")')->count()
        );
    }

    public function testRemoveTrailingSlashAction(){

        $client = static::createClient();
        $client->followRedirects();
        $crawler = $client->request('GET', '/leaderboard/');

        $this->assertGreaterThan(
            0,
            $crawler->filter('html:contains("Level")')->count()
        );
    }
} 