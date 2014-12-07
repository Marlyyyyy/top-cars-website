<?php
/**
 * Created by PhpStorm.
 * User: Marci
 * Date: 03/12/14
 * Time: 15:57
 */

namespace Marton\TopCarsBundle\Tests\Controller;

use Marton\TopCarsBundle\Test\WebTestCase;

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

        $client = $this->registerClient();

        // Check if the user has been created
        $user = $this->em->getRepository('MartonTopCarsBundle:User')->findDetailsOfUser("TestUser");

        $this->assertEquals(1,count($user));

        // Logout (after registration's automatic login)
        $crawler = $client->request('GET', '/logout');

        // Check if the user is logged out
        $container = $client->getContainer();
        $securityContext = $container->get('security.context');

        $this->assertTrue($securityContext->isGranted('IS_AUTHENTICATED_ANONYMOUSLY'));
    }

    // Test for loading the Account page
    public function testAccountAction(){

        $client = $this->loginClient();

        $crawler = $client->request('GET', '/account');

        $this->assertGreaterThan(
            0,
            $crawler->filter('html:contains("Delete Account")')->count()
        );
    }

    // Test updating the account through a form
    public function testUpdateAccountAction(){

        $client = $this->loginClient();

        $crawler = $client->request('GET', '/account');

        $form = $crawler->selectButton('userDetails_save')->form();

        $form['userDetails[country]'] = "Austria";

        $client->submit($form);

        // Check if the user's details have been updated
        $user = $this->em->getRepository('MartonTopCarsBundle:User')->findDetailsOfUser("TestUser");

        $this->assertEquals("Austria", $user->getDetails()->getCountry());
    }

    // Test authorisation and deleting an account
    public function testLoginAndDeleteAction(){

        $client = $this->loginClient();

        // Check if the user is logged in
        $container = $client->getContainer();
        $securityContext = $container->get('security.context');
        $this->assertTrue($securityContext->isGranted('ROLE_USER'));

        // Now delete the user
        $this->deleteClient($client);

        $user = $this->em->getRepository('MartonTopCarsBundle:User')->findDetailsOfUser("TestUser");

        // Check if the user has been deleted
        $this->assertEquals(0,count($user));
    }
} 