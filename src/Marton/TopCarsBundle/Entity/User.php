<?php
/**
 * Created by PhpStorm.
 * User: Marci
 * Date: 28/10/14
 * Time: 19:47
 */

namespace Marton\TopCarsBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
//use Symfony\Component\Security\Core\Role\Role;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass="Marton\TopCarsBundle\Repository\UserRepository")
 * @UniqueEntity(fields="email", message="Email already taken")
 * @UniqueEntity(fields="username", message="Username already taken")
 * @ORM\Table(name="tbl_user")
 */
class User implements UserInterface, \Serializable{

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank()
     * @Assert\Email()
     */
    private $email;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank()
     * @Assert\Length(max = 4096)
     */
    private $password;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $salt;

    /**
     * @ORM\Column(type="string", length=25, unique=true)
     * @Assert\NotBlank()
     */
    private $username;

    /**
     * @ORM\OneToOne(targetEntity="UserProgress")
     */
    private $progress;

    /**
     * @ORM\ManyToMany(targetEntity="Role", inversedBy="users")
     * @ORM\JoinTable(name="user_role",
     *      joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="role_id", referencedColumnName="id")}
     * )
     *
     */
    private $roles;

    /**
     * @ORM\ManyToMany(targetEntity="Car", inversedBy="users")
     * @ORM\JoinTable(name="user_car",
     *      joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="car_id", referencedColumnName="id")}
     * )
     *
     */
    private $cars;

    /**
     * @ORM\OneToMany(targetEntity="SuggestedCar", mappedBy="user", cascade={"persist"})
     *
     */
    private $suggestedCars;

    /**
     * @ORM\ManyToMany(targetEntity="SuggestedCar", inversedBy="upVotedUsers", cascade={"remove", "persist"})
     * @ORM\JoinTable(name="upVotedUser_suggestedCar",
     *      joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="suggestedCar_id", referencedColumnName="id")}
     * )
     *
     */
    private $votedSuggestedCars;

    // Array to store calculated statistics
    private $statistics;

    public function setStatistics($statistics)
    {
        $this->statistics = $statistics;
    }

    public function getStatistics()
    {
        return $this->statistics;
    }

    public function __construct()
    {
        $this->roles = new ArrayCollection();
        $this->cars  = new ArrayCollection();
        $this->suggestedCars  = new ArrayCollection();
        $this->votedSuggestedCars = new ArrayCollection();
        $this->salt  = md5(uniqid(null, true));
    }

    public function getId()
    {
        return $this->id;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function setEmail($email)
    {
        $this->email = $email;
    }

    public function setPassword($password)
    {
        $this->password = $password;
    }

    public function setUsername($username)
    {
        $this->username = $username;
    }

    public function setProgress($progress)
    {
        $this->progress = $progress;
    }

    public function getProgress()
    {
        return $this->progress;
    }

    /**
     * Returns the cars owned by the user.
     * @return Car[]
     */
    public function getCars()
    {
        return $this->cars->toArray();
    }

    /**
     * Add cars
     *
     * @param \Marton\TopCarsBundle\Entity\Car $car
     * @return User
     */
    public function addCar(\Marton\TopCarsBundle\Entity\Car $car) {
        $this->cars[] = $car;
        $car->addUser($this);

        return $this;
    }

    /**
     * Returns cars suggested by the user.
     * @return SuggestedCar[]
     */
    public function getSuggestedCars()
    {
        return $this->suggestedCars->toArray();
    }

    /**
     * Add suggested cars
     *
     * @param \Marton\TopCarsBundle\Entity\SuggestedCar $suggestedCar
     * @return User
     */
    public function addSuggestedCar(\Marton\TopCarsBundle\Entity\SuggestedCar $suggestedCar) {
        $this->suggestedCars[] = $suggestedCar;
        $suggestedCar->addUpVotedUsers($this);

        return $this;
    }

    /**
     * Returns suggestedCars upvoted by the user.
     * @return SuggestedCar[]
     */
    public function getVotedSuggestedCars()
    {
        return $this->votedSuggestedCars;
    }

    /**
     * Add suggested cars
     *
     * @param \Marton\TopCarsBundle\Entity\SuggestedCar $suggestedCar
     * @return User
     */
    public function addVotedSuggestedCars(\Marton\TopCarsBundle\Entity\SuggestedCar $suggestedCar) {
        $this->votedSuggestedCars[] = $suggestedCar;
        $suggestedCar->addUpVotedUsers($this);

        return $this;
    }

    /**
     * Remove suggested car
     *
     * @param \Marton\TopCarsBundle\Entity\SuggestedCar $suggestedCar
     * @return User
     */
    public function removeVotedSuggestedCars(\Marton\TopCarsBundle\Entity\SuggestedCar $suggestedCar) {
        $this->votedSuggestedCars->removeElement($suggestedCar);

        return $this;
    }

    /**
     * Returns the roles granted to the user.

     * <code>
     * public function getRoles()
     * {
     *     return array('ROLE_USER');
     * }
     * </code>
     *
     * Alternatively, the roles might be stored on a ``roles`` property,
     * and populated in any number of different ways when the user object
     * is created.
     *
     * @return Role[] The user roles
     */
    public function getRoles()
    {
        return $this->roles->toArray();
    }

    /**
     * Add roles
     *
     * @param \Marton\TopCarsBundle\Entity\Role $role
     * @return User
     */
    public function addRole(\Marton\TopCarsBundle\Entity\Role $role) {
        $this->roles[] = $role;
        $role->addUser($this);

        return $this;
    }

    /**
     * Returns the password used to authenticate the user.
     *
     * This should be the encoded password. On authentication, a plain-text
     * password will be salted, encoded, and then compared to this value.
     *
     * @return string The password
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Returns the salt that was originally used to encode the password.
     *
     * This can return null if the password was not encoded using a salt.
     *
     * @return string|null The salt
     */
    public function getSalt()
    {
        return $this->salt;
    }

    /**
     * Returns the username used to authenticate the user.
     *
     * @return string The username
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Removes sensitive data from the user.
     *
     * This is important if, at any given point, sensitive information like
     * the plain-text password is stored on this object.
     */
    public function eraseCredentials()
    {
    }

    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * String representation of object
     * @link http://php.net/manual/en/serializable.serialize.php
     * @return string the string representation of the object or null
     */
    public function serialize()
    {
        return serialize(array(
            $this->id,
            $this->email,
            $this->password,
            // see section on salt below
            $this->salt
        ));
    }

    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * Constructs the object
     * @link http://php.net/manual/en/serializable.unserialize.php
     * @param string $serialized <p>
     * The string representation of the object.
     * </p>
     * @return void
     */
    public function unserialize($serialized)
    {
        list (
            $this->id,
            $this->email,
            $this->password,
            // see section on salt below
            $this->salt
            ) = unserialize($serialized);
    }
}