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
use JsonSerializable;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="Marton\TopCarsBundle\Repository\SuggestedCarRepository")
 * @ORM\Table(name="tbl_car_suggest")
 * @ORM\HasLifecycleCallbacks()
 */
class SuggestedCar implements JsonSerializable{

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
    protected $image = "default.png";

    /**
     *
     * @var File
     *
     * @Assert\File(
     *     maxSize = "5M",
     *     maxSizeMessage = "The maxmimum allowed file size is 5MB."
     * )
     */
    protected $image_file;

    /**
     * @ORM\Column(type="integer")
     */
    protected $speed = 0;

    /**
     * @ORM\Column(type="integer")
     */
    protected $power = 0;

    /**
     * @ORM\Column(type="integer")
     */
    protected $torque = 0;

    /**
     * @ORM\Column(type="float")
     */
    protected $acceleration = 0;

    /**
     * @ORM\Column(type="integer")
     */
    protected $weight = 0;

    /**
     * @ORM\Column(type="string", length=255)
     */
    protected $comment = "Please add this car!";

    /**
     * @ORM\Column(type="datetime")
     */
    protected $createdAt;

    /**
     * @ORM\ManyToOne(targetEntity="User", inversedBy="suggestedCars")
     *
     */
    private $user;

    /**
     * @ORM\ManyToMany(targetEntity="User", mappedBy="votedSuggestedCars", cascade={"persist"})
     *
     */
    private $upVotedUsers;

    public function __construct()
    {
        $this->upVotedUsers = new ArrayCollection();
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

    public function setImageFile($image_file)
    {
        $this->image_file = $image_file;
    }

    public function getImageFile()
    {
        return $this->image_file;
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
     * @ORM\PrePersist()
     * @ORM\PreUpdate()
     */
    public function preUpload()
    {
        $this->createdAt = new \DateTime();
    }

    /**
     * Called before entity removal
     *
     * @ORM\PreRemove()
     */
    public function removeUpload()
    {

    }

    /**
     * Called after entity persistence
     *
     * @ORM\PostPersist()
     * @ORM\PostUpdate()
     */
    public function upload()
    {

        // Clean up the file property as we won't need it anymore
        $this->image_file = null;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function setUser($user)
    {
        $this->user = $user;
    }

    public function getUser()
    {
        return $this->user;
    }

    /**
     * Add users
     *
     * @param \Marton\TopCarsBundle\Entity\User $users
     * @return SuggestedCar
     */
    public function addUpVotedUsers(\Marton\TopCarsBundle\Entity\User $users)
    {
        $this->upVotedUsers[] = $users;

        return $this;
    }

    public function getUpVotedUsers()
    {
        return $this->upVotedUsers->toArray();
    }

    public function jsonSerialize()
    {
        return array(
            'id' => $this->id,
            'model'=> $this->model,
            'image' => $this->image,
            'speed' => $this->speed,
            'power' => $this->power,
            'torque' => $this->torque,
            'acceleration' => $this->acceleration,
            'weight' => $this->weight,
            'comment' => $this->comment
        );
    }
} 