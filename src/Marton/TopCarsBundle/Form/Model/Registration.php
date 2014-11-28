<?php
/**
 * Created by PhpStorm.
 * User: Marci
 * Date: 28/10/14
 * Time: 21:55
 */

namespace Marton\TopCarsBundle\Form\Model;

use Symfony\Component\Validator\Constraints as Assert;

use Marton\TopCarsBundle\Entity\User;

class Registration
{
    /**
     * @Assert\Type(type="Marton\TopCarsBundle\Entity\User")
     * @Assert\Valid()
     */
    protected $user;

    /**
     * @Assert\NotBlank(message=null)
     * @Assert\True(message="You cannot register without accepting the rules")
     */
    protected $termsAccepted;

    public function setUser(User $user)
    {
        $this->user = $user;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function getTermsAccepted()
    {
        return $this->termsAccepted;
    }

    public function setTermsAccepted($termsAccepted)
    {
        $this->termsAccepted = (Boolean) $termsAccepted;
    }
}