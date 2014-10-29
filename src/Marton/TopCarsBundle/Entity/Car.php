<?php
/**
 * Created by PhpStorm.
 * User: Marci
 * Date: 29/10/14
 * Time: 21:49
 */

namespace Marton\TopCarsBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="tbl_car")
 */

class Car {

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
     * @ORM\Column(type="integer")
     */
    private $price = 0;

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

    public function setPrice($price)
    {
        $this->price = $price;
    }

    public function getPrice()
    {
        return $this->price;
    }
} 