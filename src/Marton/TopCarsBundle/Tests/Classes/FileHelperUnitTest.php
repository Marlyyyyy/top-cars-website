<?php
/**
 * Created by PhpStorm.
 * User: Marci
 * Date: 07/12/14
 * Time: 16:09
 */

namespace Marton\TopCarsBundle\Tests\Classes;


use Marton\TopCarsBundle\Services\FileHelper;

class FileHelperUnitTest extends \PHPUnit_Framework_TestCase{

    public function testGuessExtension(){

        $fileHelper = new FileHelper();

        $guessedExtension = $fileHelper->guessExtension("FluffyDog.png");
        $this->assertEquals("png", $guessedExtension);
    }

    // Test what happens to the filename if a user logged in the same account twice
    // wants to upload the same picture at the same time
    public function testMakeUniqueName(){

        $fileHelper = new FileHelper();

        $firstUniqueName = $fileHelper->makeUniqueName("1", "FluffyDog.png");
        $secondUniqueName = $fileHelper->makeUniqueName("1", "FluffyDog.png");

        $this->assertNotEquals($firstUniqueName, $secondUniqueName);
    }
} 