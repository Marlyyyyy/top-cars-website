<?php
/**
 * Created by PhpStorm.
 * User: Marci
 * Date: 03/12/14
 * Time: 15:57
 */

namespace Marton\TopCarsBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AccountControllerTest extends WebTestCase{

    // Test for loading the Registration page
    public function testRegisterAction(){

        $client = static::createClient();

        $crawler = $client->request('GET', '/register');

        $this->assertGreaterThan(
            0,
            $crawler->filter('html:contains("Registration")')->count()
        );
    }

    // Test for loading the Login page
    public function testLoginAction(){

        $client = static::createClient();
        $client->followRedirects();

        // Login
        $crawler = $client->request('GET', '/login');

        $this->assertGreaterThan(
            0,
            $crawler->filter('html:contains("Login")')->count()
        );

    }

    // Test creating an account
    public function testCreateAction(){

        $client = static::createClient();
        $client->followRedirects();

        // Register
        $crawler = $client->request('GET', '/register');

        $form = $crawler->selectButton('registration_register')->form();

        $form['registration[user][username]'] = 'TestUser';
        $form['registration[user][email]'] = 'test@test.com';
        $form['registration[user][password][password]'] = 'testpw';
        $form['registration[user][password][confirm]'] = 'testpw';
        $form['registration[terms]']->tick();

        $client->submit($form);

        // Check if the user has been created
        $em = $client->getContainer()->get('doctrine.orm.entity_manager');
        $user = $em->getRepository('MartonTopCarsBundle:User')->findDetailsOfUser("TestUser");

        $this->assertCount(1,$user);

        // Logout (after registration's automatic login)
        $crawler = $client->request('GET', '/logout');

        // Check if the user is logged out
        $container = $client->getContainer();
        $securityContext = $container->get('security.context');

        $this->assertTrue($securityContext->isGranted('IS_AUTHENTICATED_ANONYMOUSLY'));
    }

    // Test logging in and deleting an account
    public function testLoginAndDeleteAction(){

        $client = static::createClient();
        $client->followRedirects();

        // Login
        $crawler = $client->request('GET', '/login');

        $form = $crawler->selectButton('button-login')->form();

        $form['_username'] = 'test@test.com';
        $form['_password'] = 'testpw';

        $client->submit($form);

        // Check if the user is logged in
        $container = $client->getContainer();
        $securityContext = $container->get('security.context');
        $this->assertTrue($securityContext->isGranted('ROLE_USER'));


        // Now delete the user
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

        $em = $client->getContainer()->get('doctrine.orm.entity_manager');
        $user = $em->getRepository('MartonTopCarsBundle:User')->findDetailsOfUser("TestUser");

        // Check if the user has been deleted
        $this->assertCount(0,$user);
    }
} 