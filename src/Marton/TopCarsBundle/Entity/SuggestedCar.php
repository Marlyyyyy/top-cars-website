<?php
/**
 * Created by PhpStorm.
 * User: Marci
 * Date: 03/11/14
 * Time: 20:32
 */

namespace Marton\TopCarsBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="tbl_car_suggest")
 * @ORM\HasLifecycleCallbacks()
 */
class SuggestedCar {

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    protected $model;

    /**
     * @ORM\Column(type="string", length=255)
     */
    protected $image;

    /**
     * @ORM\Column(type="integer")
     */
    protected $speed;

    /**
     * @ORM\Column(type="integer")
     */
    protected $power;

    /**
     * @ORM\Column(type="integer")
     */
    protected $torque;

    /**
     * @ORM\Column(type="float")
     */
    protected $acceleration;

    /**
     * @ORM\Column(type="integer")
     */
    protected $weight;

    /**
     * @ORM\Column(type="string", length=255)
     */
    protected $comment;

    /**
     * @ORM\Column(type="datetime")
     */
    protected $createdAt;

    /**
     * @ORM\ManyToMany(targetEntity="User", mappedBy="suggestedCars")
     *
     */
    private $users;

    public function __construct()
    {
        $this->users = new ArrayCollection();
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setAcceleration($acceleration)
    {
        $this->acceleration = $acceleration;
    }

    public function getAcceleration()
    {
        return $this->acceleration;
    }

    public function setComment($comment)
    {
        $this->comment = $comment;
    }

    public function getComment()
    {
        return $this->comment;
    }

    public function setImage($image)
    {
        $this->image = $image;
    }

    public function getImage()
    {
        return $this->image;
    }

    public function setModel($model)
    {
        $this->model = $model;
    }

    public function getModel()
    {
        return $this->model;
    }

    public function setPower($power)
    {
        $this->power = $power;
    }

    public function getPower()
    {
        return $this->power;
    }

    public function setSpeed($speed)
    {
        $this->speed = $speed;
    }

    public function getSpeed()
    {
        return $this->speed;
    }

    public function setTorque($torque)
    {
        $this->torque = $torque;
    }

    public function getTorque()
    {
        return $this->torque;
    }

    public function setWeight($weight)
    {
        $this->weight = $weight;
    }

    public function getWeight()
    {
        return $this->weight;
    }

    /**
     * @ORM\PrePersist
     */
    public function setCreatedAt()
    {
        $this->createdAt = new \DateTime();
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Add users
     *
     * @param \Marton\TopCarsBundle\Entity\User $users
     * @return SuggestedCar
     */
    public function addUser(\Marton\TopCarsBundle\Entity\User $users)
    {
        $this->users[] = $users;

        return $this;
    }

    public function getUsers()
    {
        return $this->users->toArray();
    }
} 