<?php
/**
 * Created by PhpStorm.
 * User: Marci
 * Date: 01/12/14
 * Time: 01:55
 */

namespace Marton\TopCarsBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="tbl_user_details")
 */
class UserDetails {

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    protected $firstName;

    /**
     * @ORM\Column(type="string", length=255)
     */
    protected $lastName;

    /**
     * @ORM\Column(type="string", length=255)
     */
    protected $profilePicturePath = "default.jpg";

    /**
     * @ORM\Column(type="string", length=255)
     */
    protected $country;

    /**
     * @ORM\Column(type="string", length=1000)
     */
    protected $about;

    public function setAbout($about)
    {
        $this->about = $about;
    }

    public function getAbout()
    {
        return $this->about;
    }

    public function setCountry($country)
    {
        $this->country = $country;
    }

    public function getCountry()
    {
        return $this->country;
    }

    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;
    }

    public function getFirstName()
    {
        return $this->firstName;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setLastName($lastName)
    {
        $this->lastName = $lastName;
    }

    public function getLastName()
    {
        return $this->lastName;
    }

    public function setProfilePicturePath($profilePicturePath)
    {
        $this->profilePicturePath = $profilePicturePath;
    }

    public function getProfilePicturePath()
    {
        return $this->profilePicturePath;
    }


} 