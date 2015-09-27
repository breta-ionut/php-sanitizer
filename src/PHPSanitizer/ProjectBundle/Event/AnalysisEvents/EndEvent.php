<?php

namespace PHPSanitizer\ProjectBundle\Event\AnalysisEvents;

use Symfony\Component\EventDispatcher\Event;
use PHPSanitizer\ProjectBundle\Entity\Project;

/**
 * Representation of an analysis end event.
 */
class EndEvent extends Event
{
    /**
     * The project which has been analyzed.
     * 
     * @var Project
     */
    protected $project;
    
    /**
     * The class constructor.
     * 
     * @param Project $project
     */
    public function __construct(Project $project)
    {
        $this->project = $project;
    }
    
    /**
     * The project getter.
     * 
     * @return Project
     */
    public function getProject()
    {
        return $this->project;
    }
}
