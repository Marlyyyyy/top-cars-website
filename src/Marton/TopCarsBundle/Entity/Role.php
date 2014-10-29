<?php
/**
 * Created by PhpStorm.
 * User: Marci
 * Date: 28/10/14
 * Time: 21:00
 */

namespace Marton\TopCarsBundle\Entity;


use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Security\Core\Role\RoleInterface;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="tbl_role")
 * @ORM\Entity()
 */
class Role implements RoleInterface {

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(name="name", type="string", length=30)
     */
    private $name;

    /**
     * @ORM\Column(name="role", type="string", length=20, unique=true)
     */
    private $role;

    /**
     * @ORM\ManyToMany(targetEntity="User", mappedBy="roles", cascade={"persist"})
     *
     */
    private $users;

    public function __construct()
    {
        $this->users = new ArrayCollection();
    }

    /**
     * @see RoleInterface
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * @param mixed $role
     */
    public function setRole($role)
    {
        $this->role = $role;
    }

    /**
     * @param mixed $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }


    public function getId()
    {
        return $this->id;
    }

    /**
     * Add users
     *
     * @param \Marton\TopCarsBundle\Entity\User $users
     * @return Role
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


    /**
     * Return the role field.
     * @return string
     */
    public function __toString()
    {
        return (string) $this->role;
    }

}