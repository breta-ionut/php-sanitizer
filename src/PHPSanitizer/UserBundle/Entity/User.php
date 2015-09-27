<?php

namespace PHPSanitizer\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use PHPSanitizer\ProjectBundle\Entity\Project;

/**
 * Representation of an user in the application.
 * 
 * @ORM\Table(name="user")
 * @ORM\Entity(repositoryClass="PHPSanitizer\UserBundle\Entity\UserRepository")
 * 
 * @UniqueEntity(
 *  fields="username",
 *  message="The provided username is already in use!",
 *  groups={"Default", "Registration"}
 * )
 * @UniqueEntity(
 *  fields="email",
 *  message="The provided e-mail is already in use!",
 *  groups={"Default", "Registration"}
 * )
 */
class User implements UserInterface, \Serializable
{
    /**
     * The internal unique id of the user.
     * 
     * @var int
     * 
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;
    
    /**
     * The nickname of the user.
     * 
     * @var string
     * 
     * @ORM\Column(type="string", length=100, unique=true, nullable=false)
     * 
     * @Assert\NotBlank(groups={"Default", "Registration"})
     * @Assert\Length(min=3, max=100, groups={"Default", "Registration"})
     */
    protected $username;
    
    /**
     * The e-mail of the user.
     * 
     * @var string
     * 
     * @ORM\Column(type="string", length=100, unique=true, nullable=false)
     * 
     * @Assert\NotBlank(groups={"Default", "Registration"})
     * @Assert\Length(max=100, groups={"Default", "Registration"})
     * @Assert\Email(groups={"Default", "Registration"})
     */
    protected $email;
    
    /**
     * The encoded password of the user.
     * 
     * @var string
     * 
     * @ORM\Column(type="string", length=64, nullable=false)
     * 
     * @Assert\NotBlank
     * @Assert\Length(max=64)
     */
    protected $password;
    
    /**
     * The plain password of the user.
     * 
     * @var string
     *
     * @Assert\NotBlank(groups={"Registration"})
     * @Assert\Length(min=8, max=4096, groups={"Registration"})
     */
    protected $plainPassword;
    
    /**
     * The projects associated to the user.
     * 
     * @var Project[]
     * 
     * @ORM\OneToMany(targetEntity="PHPSanitizer\ProjectBundle\Entity\Project", mappedBy="user")
     */
    protected $projects;
    
    /**
     * The class constructor.
     */
    public function __construct()
    {
        $this->projects = new ArrayCollection();
    }
    
    /**
     * {@inheritdoc}
     */
    public function getRoles()
    {
        return array('ROLE_USER');
    }
    
    /**
     * {@inheritdoc}
     */
    public function getSalt()
    {
        return null;
    }
    
    /**
     * {@inheritdoc}
     */
    public function eraseCredentials()
    {
    }
    
    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        return serialize(array(
            $this->id,
            $this->username,
            $this->password,
        ));
    }
    
    /**
     * {@inheritdoc}
     */
    public function unserialize($serialized) {
        list(
            $this->id,
            $this->username,
            $this->password
        ) = unserialize($serialized);
    }
    
    /**
     * Id getter.
     * 
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }
        
    /**
     * {@inheritdoc}
     */
    public function getUsername()
    {
        return $this->username;
    }
    
    /**
     * Username setter.
     * 
     * @param string $username
     * 
     * @return User
     */
    public function setUsername($username)
    {
        $this->username = $username;
        
        return $this;
    }
    
    /**
     * E-mail getter.
     * 
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }
    
    /**
     * E-mail setter.
     * 
     * @param string $email
     * 
     * @return User
     */
    public function setEmail($email)
    {
        $this->email = $email;
        
        return $this;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getPassword()
    {
        return $this->password;
    }
    
    /**
     * Password setter.
     * 
     * @param string $password
     * 
     * @return User
     */
    public function setPassword($password)
    {
        $this->password = $password;
        
        return $this;
    }
    
    /**
     * Plain password getter.
     * 
     * @return string
     */
    public function getPlainPassword()
    {
        return $this->plainPassword;
    }
    
    /**
     * Plain password setter.
     * 
     * @param string $plainPassword
     * 
     * @return User
     */
    public function setPlainPassword($plainPassword)
    {
        $this->plainPassword = $plainPassword;
        
        return $this;
    }
    
    /**
     * Associates a project to the user.
     * 
     * @param Project $project
     * 
     * @return User
     */
    public function addProject(Project $project)
    {
        $this->projects[] = $project;
        
        return $this;
    }
    
    /**
     * Projects getter.
     * 
     * @return Project[]
     */
    public function getProjects()
    {
        return $this->projects;
    }
}
