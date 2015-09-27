<?php

namespace PHPSanitizer\ProjectBundle\Entity\AnalyzerResponse;

/**
 * Encapsulates the dependency analysis results.
 */
class Dependency implements AnalyzerResponseInterface
{
    /**
     * The collection of modules.
     * 
     * @var array
     */
    protected $modules;
    
    /**
     * The dependency graph.
     * 
     * @var array
     */
    protected $graph;
    
    /**
     * The collection of connected components.
     * 
     * @var array
     */
    protected $connectedComponents;
    
    /**
     * The dependency score.
     * 
     * @var int
     */
    protected $score;
    
    /**
     * The class constructor.
     * 
     * @param array $modules
     * @param array $graph
     * @param array $connectedComponents
     * @param int $score
     */
    public function __construct(array $modules, array $graph, array $connectedComponents, $score)
    {
        $this->modules = $modules;
        $this->graph = $graph;
        $this->connectedComponents = $connectedComponents;
        $this->score = $score;
    }
    
    /**
     * {@inheritdoc}
     */
    public function isEmpty()
    {
        return empty($this->modules);
    }
    
    /**
     * {@inheritdoc}
     */
    public static function createEmptyResponse()
    {
        return new static(array(), array(), array(), 0);
    }
    
    /**
     * Fetches the modules.
     * 
     * @return array
     */
    public function getModules()
    {
        return $this->modules;
    }
    
    /**
     * Fetches the dependency graph.
     * 
     * @return array
     */
    public function getGraph()
    {
        return $this->graph;
    }
    
    /**
     * Fetches the set of conencted components.
     * 
     * @return array
     */
    public function getConnectedComponents()
    {
        return $this->connectedComponents;
    }
    
    /**
     * Fetches the dependency score.
     * 
     * @return int
     */
    public function getScore()
    {
        return $this->score;
    }
}
