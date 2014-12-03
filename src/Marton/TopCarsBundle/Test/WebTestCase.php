<?php
/**
 * Created by PhpStorm.
 * User: Marci
 * Date: 03/12/14
 * Time: 19:58
 */

namespace Marton\TopCarsBundle\Test;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase as WTC;

class WebTestCase extends WTC{

    public function registerClient($username = 'TestUser', $email = 'test@test.com'){

        $client = static::createClient();
        $client->followRedirects();

        // Register
        $crawler = $client->request('GET', '/register');

        $form = $crawler->selectButton('registration_register')->form();

        $form['registration[user][username]'] = $username;
        $form['registration[user][email]'] = $email;
        $form['registration[user][password][password]'] = 'testpw';
        $form['registration[user][password][confirm]'] = 'testpw';
        $form['registration[terms]']->tick();

        $client->submit($form);

        return $client;
    }

    public function loginClient($username = 'TestUser'){

        $client = static::createClient();
        $client->followRedirects();

        // Login
        $crawler = $client->request('GET', '/login');

        $form = $crawler->selectButton('button-login')->form();

        $form['_username'] = $username;
        $form['_password'] = 'testpw';

        $client->submit($form);

        return $client;
    }

    public function deleteClient($client){

        $client->request(
            'GET',
            '/account/delete',
            array(),
            array(),
            array(
                'CONTENT_TYPE'          => 'application/json',
                'HTTP_REFERER'          => '/account',
                'HTTP_X-Requested-With' => 'XMLHttpRequest',
            )
        );
    }
} 