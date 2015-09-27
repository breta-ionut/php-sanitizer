<?php

namespace PHPSanitizer\ProjectBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use PHPSanitizer\UserBundle\Entity\User;
use PHPSanitizer\ProjectBundle\Entity\Analysis;

/**
 * The project model.
 * 
 * @ORM\Table(name="project", uniqueConstraints={@UniqueConstraint(name="unique_name_user", columns={"name", "uid"})})
 * @ORM\Entity
 * 
 * @UniqueEntity(
 *  fields={"name", "user"},
 *  message="You already have a project with this name! Please select another one!",
 *  groups={"Default", "Edit"}
 * )
 */
class Project
{
    /**
     * The internal unique id of the project.
     * 
     * @var int
     * 
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;
    
    /**
     * The name of the project.
     * 
     * @var string
     * 
     * @ORM\Column(type="string", length=100, nullable=false)
     * 
     * @Assert\NotBlank(groups={"Default", "Edit"})
     * @Assert\Length(min=3, max=100, groups={"Default", "Edit"})
     * @Assert\Regex(
     *  pattern="/[\w-]+/",
     *  message="The name of the project can only contain digits, letters, underscores and hyphens!",
     *  groups={"Default", "Edit"}
     * )
     */
    protected $name;
    
    /**
     * The user who owns the project.
     * 
     * @var User
     * 
     * @ORM\ManyToOne(targetEntity="PHPSanitizer\UserBundle\Entity\User", inversedBy="projects")
     * @ORM\JoinColumn(name="uid", referencedColumnName="id")
     */
    protected $user;
    
    /**
     * The source files hash. It is used to track the current version of the project.
     * 
     * @var string
     * 
     * @ORM\Column(type="string", length=64, nullable=false)
     */
    protected $hash;
    
    /**
     * The path to the project's modules. It is used to analyze the dependency between them.
     * 
     * @var string
     * 
     * @ORM\Column(type="string", length=1024, nullable=false)
     * 
     * @Assert\NotBlank(groups={"Default", "Edit"})
     */
    protected $modulesPath;
    
    /**
     * Flag which indicates if the project is being analyzed.
     * 
     * @var boolean
     * 
     * @ORM\Column(type="boolean", nullable=false, options={"default"=false})
     */
    protected $analyzing = false;
    
    /**
     * Represents the time when the entity was created.
     * 
     * @var \DateTime
     * 
     * @ORM\Column(type="datetime")
     */
    protected $created;
    
    /**
     * Represents the time when the entity was last updated.
     * 
     * @var \DateTime
     * 
     * @ORM\Column(type="datetime")
     */
    protected $updated;
    
    /**
     * The file representing the source code of the project.
     * 
     * @var UploadedFile
     * 
     * @Assert\NotBlank(message="You should upload the source code of the project!")
     * @Assert\File(
     *  mimeTypes={"application/octet-stream", "application/zip"},
     *  mimeTypesMessage="The source code of the project must be a ZIP archive!",
     *  groups={"Default", "Edit"}
     * )
     */
    protected $file;
    
    /**
     * Stores the path to the source code of the file.
     * 
     * @var string
     * 
     * @ORM\Column(type="string", length=4096, nullable=false)
     */
    protected $filepath;
    
    /**
     * The collection of analyses of the project.
     * 
     * @var Analysis[]
     * 
     * @ORM\OneToMany(targetEntity="PHPSanitizer\ProjectBundle\Entity\Analysis", mappedBy="project", cascade={"remove"})
     * @ORM\OrderBy({"created" = "ASC"})
     */
    protected $analyses;
    
    /**
     * The class constructor.
     */
    public function __construct()
    {
        $this->analyses = new ArrayCollection();
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
     * Name getter.
     * 
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
    
    /**
     * Name setter.
     * 
     * @param string $name
     * 
     * @return Project
     */
    public function setName($name)
    {
        $this->name = $name;
        
        return $this;
    }
    
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
     * @return Project
     */
    public function setUser(User $user)
    {
        $user->addProject($this);
        $this->user = $user;
        
        return $this;
    }
    
    /**
     * Hash getter. 
     * 
     * @return string
     */
    public function getHash()
    {
        return $this->hash;
    }
    
    /**
     * Hash setter.
     * 
     * @param string $hash
     * 
     * @return Project
     */
    public function setHash($hash)
    {
        $this->hash = $hash;
        
        return $this;
    }
    
    /**
     * Modules path getter.
     * 
     * @return string
     */
    public function getModulesPath()
    {
        return $this->modulesPath;
    }
    
    /**
     * Modules path setter.
     * 
     * @param string $modulesPath
     * 
     * @return Project
     */
    public function setModulesPath($modulesPath)
    {
        $this->modulesPath = $modulesPath;
        
        return $this;
    }
    
    /**
     * Analyzing flag getter.
     * 
     * @return boolean
     */
    public function getAnalyzing()
    {
        return $this->analyzing;
    }
    
    /**
     * Analyzing flag setter.
     * 
     * @param boolean $analyzing
     * 
     * @return Project
     */
    public function setAnalyzing($analyzing)
    {
        $this->analyzing = $analyzing;
        
        return $this;
    }
    
    /**
     * Project file getter.
     * 
     * @return UploadedFile
     */
    public function getFile()
    {
        return $this->file;
    }
    
    /**
     * Project file setter.
     * 
     * @param UploadedFile $file
     * 
     * @return Project
     */
    public function setFile(UploadedFile $file = null)
    {
        $this->file = $file;
        
        return $this;
    }
    
    /**
     * Filepath getter.
     * 
     * @return string
     */
    public function getFilepath()
    {
        return $this->filepath;
    }
    
    /**
     * Filepath setter.
     * 
     * @param string $filepath
     * 
     * @return Project
     */
    public function setFilepath($filepath)
    {
        $this->filepath = $filepath;
        
        return $this;
    }
    
    /**
     * Gets the time when the project was created.
     * 
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }
    
    /**
     * Sets the time when the project was created.
     * 
     * @return Project
     */
    public function setCreated()
    {
        $this->created = new \DateTime('now');
        
        return $this;
    }
    
    /**
     * Gets the time when the project was last updated.
     * 
     * @return \DateTime
     */
    public function getUpdated()
    {
        return $this->updated;
    }
    
    /**
     * Sets the time when the project was updated.
     * 
     * @return Project
     */
    public function setUpdated()
    {
        $this->updated = new \DateTime('now');
        
        return $this;
    }
    
    /**
     * Adds an analysis to the project.
     * 
     * @param Analysis $analysis
     */
    public function addAnalysis(Analysis $analysis)
    {
        $this->analyses[] = $analysis;
    }
    
    /**
     * Gets the analyses of the project.
     * 
     * @return Analysis[]
     */
    public function getAnalyses()
    {
        return $this->analyses;
    }
}
