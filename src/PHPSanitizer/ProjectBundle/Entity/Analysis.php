<?php

namespace PHPSanitizer\ProjectBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use PHPSanitizer\ProjectBundle\Entity\Project;
use PHPSanitizer\ProjectBundle\Entity\AnalyzerResponse\Dependency as DependencyResponse;
use PHPSanitizer\ProjectBundle\Entity\AnalyzerResponse\PHPMD as PHPMDResponse;

/**
 * Analysis model representation.
 * 
 * @ORM\Table(name="analysis")
 * @ORM\Entity
 */
class Analysis
{
    /**
     * The internal unique id of the analysis.
     * 
     * @var int
     * 
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;
    
    /**
     * The analyzed project.
     * 
     * @var Project
     * 
     * @ORM\ManyToOne(targetEntity="PHPSanitizer\ProjectBundle\Entity\Project", inversedBy="analyses")
     * @ORM\JoinColumn(name="pid", referencedColumnName="id")
     */
    protected $project;
    
    /**
     * The time when the analysis representation was created.
     * 
     * @var \DateTime
     * 
     * @ORM\Column(type="datetime")
     */
    protected $created;

    /**
     * Flag which indicates if the analysis was viewed.
     * 
     * @var boolean
     * 
     * @ORM\Column(type="boolean", nullable=false, options={"default"=true})
     */
    protected $new = true;
    
    /**
     * The representation of the dependency analysis results.
     * 
     * @var DependencyResponse 
     *
     * @ORM\Column(type="object")
     */
    protected $dependencyResults;
    
    /**
     * The representation of the PHPMD analysis results.
     * 
     * @var PHPMDResponse
     * 
     * @ORM\Column(type="object")
     */
    protected $phpMDResults;
        
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
     * Project getter.
     * 
     * @return Project
     */
    public function getProject()
    {
        return $this->project;
    }
    
    /**
     * Project setter.
     * 
     * @param Project $project
     * 
     * @return Analysis
     */
    public function setProject(Project $project)
    {
        $project->addAnalysis($this);
        $this->project = $project;
        
        return $this;
    }
    
    /**
     * Dependency results getter.
     * 
     * @return DependencyResponse
     */
    public function getDependencyResults()
    {
        return $this->dependencyResults;
    }
    
    /**
     * Creation time getter.
     * 
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }
    
    /**
     * Creation time setter.
     * 
     * @return Analysis
     */
    public function setCreated()
    {
        $this->created = new \DateTime;
        
        return $this;
    }
    
    /**
     * New flag getter.
     * 
     * @return boolean
     */
    public function getNew()
    {
        return $this->new;
    }
    
    /**
     * New flag setter.
     * 
     * @param boolean $new
     * 
     * @return Analysis
     */
    public function setNew($new)
    {
        $this->new = (bool) $new;
    
        return $this;
    }
    
    /**
     * Dependency results setter.
     * 
     * @param DependencyResponse $dependencyResults
     * 
     * @return Analysis
     */
    public function setDependencyResults(DependencyResponse $dependencyResults)
    {
        $this->dependencyResults = $dependencyResults;
        
        return $this;
    }
    
    /**
     * PHPMD results getter.
     * 
     * @return PHPMDResponse
     */
    public function getPHPMDResults()
    {
        return $this->phpMDResults;
    }
    
    /**
     * PHPMD results setter.
     * 
     * @param PHPMDResponse $phpMDResults
     * 
     * @return Analysis
     */
    public function setPHPMDResults(PHPMDResponse $phpMDResults)
    {
        $this->phpMDResults = $phpMDResults;
        
        return $this;
    }
}
