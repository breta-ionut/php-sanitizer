<?php

namespace PHPSanitizer\ProjectBundle\Event\ProjectEvents;

use Symfony\Component\EventDispatcher\Event;
use PHPSanitizer\ProjectBundle\Entity\Project;
use PHPSanitizer\ProjectBundle\Service\ProjectManager;

/**
 * The representation of the source change project event.
 */
class SourceChangeEvent extends Event
{
    /**
     * The project whose source code was changed.
     * 
     * @var Project
     */
    protected $project;
    
    /**
     * The project manager service.
     * 
     * @var ProjectManager
     */
    protected $projectManager;
    
    /**
     * The class constructor.
     * 
     * @param Project $project
     * @param ProjectManager $projectManager
     */
    public function __construct(Project $project, ProjectManager $projectManager)
    {
        $this->project = $project;
        $this->projectManager = $projectManager;
    }
    
    /**
     * Fetches the associated product.
     * 
     * @return Project
     */
    public function getProject()
    {
        return $this->project;
    }
    
    /**
     * Fetches the project manager service.
     * 
     * @return ProjectManager
     */
    public function getProjectManager()
    {
        return $this->projectManager;
    }
}
