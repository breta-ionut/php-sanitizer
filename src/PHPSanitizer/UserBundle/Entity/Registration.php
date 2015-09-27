<?php

namespace PHPSanitizer\UserBundle\Entity;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Represents the user registration model.
 */
class Registration
{
    /**
     * The user attached to the registration.
     * 
     * @var User
     * 
     * @Assert\NotBlank()
     * @Assert\Valid()
     */
    protected $user;
    
    /**
     * Marks if the user accepted the terms.
     * 
     * @var boolean
     * 
     * @Assert\NotBlank()
     * @Assert\True()
     */
    protected $termsAccepted;
    
    /**
     * User getter.
     * 
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }
    
    /**
     * User setter.
     * 
     * @param User $user
     * 
     * @return Registration
     */
    public function setUser(User $user)
    {
        $this->user = $user;
        
        return $this;
    }
    
    /**
     * Terms accepted getter.
     * 
     * @return boolean
     */
    public function getTermsAccepted()
    {
        return $this->termsAccepted;
    }
    
    /**
     * Terms accepted setter.
     * 
     * @param boolean $termsAccepted
     * 
     * @return Registration
     */
    public function setTermsAccepted($termsAccepted)
    {
        $this->termsAccepted = $termsAccepted;
        
        return $this;
    }
}
